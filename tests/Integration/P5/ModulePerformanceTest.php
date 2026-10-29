<?php

namespace Tests\Integration\P5;

use App\Models\Order;
use App\Services\InventoryService;
use App\Services\Ordering\PlaceOrder;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\DB;
use Tests\Support\MySqlTestCase;

class ModulePerformanceTest extends MySqlTestCase
{
    public function test_financial_use_cases_keep_bounded_queries(): void
    {
        $this->seedCommerce();
        DB::table('users')->where('id', 910002)->update(['role' => 'admin']);
        config(['ordering.transitions_enabled' => true, 'vnpay.enabled' => true,
            'vnpay.vnp_tmn_code' => 'P5MERCHANT', 'vnpay.vnp_hash_secret' => 'synthetic-p5-secret',
            'vnpay.vnp_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', 'vnpay.vnp_return_url' => 'http://localhost/api/vnpay/return']);
        $metrics = [];
        $measure = function (string $name, callable $work) use (&$metrics) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $start = hrtime(true);
            try {
                return $work();
            } finally {
                $metrics[$name] = ['queries' => count(DB::getQueryLog()), 'elapsed_ms' => round((hrtime(true) - $start) / 1e6, 3)];
                DB::disableQueryLog();
            }
        };
        $payload = $this->checkoutPayload();
        $order = $measure('checkout_cod', fn () => app(PlaceOrder::class)->place($payload, 910001));
        $measure('checkout_replay', fn () => app(PlaceOrder::class)->place($payload, 910001));
        $measure('confirm', fn () => app(OrderService::class)->changeStatus($order, 'confirmed', 910002, 'profile-confirm'));
        $measure('shipping', fn () => app(OrderService::class)->changeStatus($order, 'shipping', 910002, 'profile-ship'));
        $measure('import', fn () => app(InventoryService::class)->import(1, 910001, 2, null, null, 910002, 'profile-import'));
        $online = app(PlaceOrder::class)->place(array_replace($this->checkoutPayload(), ['payment_method' => 'vnpay']), 910001);
        $attempt = DB::table('payment_attempts')->where('order_id', $online->id)->first();
        $query = ['vnp_Amount' => '2500000000', 'vnp_TmnCode' => 'P5MERCHANT', 'vnp_TxnRef' => $attempt->reference,
            'vnp_TransactionNo' => '555555', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00'];
        ksort($query);
        $query['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($query, '', '&', PHP_QUERY_RFC1738), 'synthetic-p5-secret');
        $measure('payment_ipn', fn () => app(PaymentService::class)->notify($query));
        $budgets = ['checkout_cod' => 35, 'checkout_replay' => 5, 'confirm' => 19, 'shipping' => 35, 'import' => 15, 'payment_ipn' => 10];
        foreach ($metrics as $name => $metric) {
            $this->assertLessThanOrEqual($budgets[$name], $metric['queries'], $name.' query budget (P4 baseline plus at most one legacy adapter read)');
        }
        $this->assertSame('paid', Order::findOrFail($online->id)->payment_status);
        if (in_array(getenv('P5_PROFILE'), ['before', 'after'], true)) {
            file_put_contents(base_path('tests/.runtime/p5-'.getenv('P5_PROFILE').'.json'), json_encode($metrics, JSON_PRETTY_PRINT));
        }
    }
}
