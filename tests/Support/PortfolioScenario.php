<?php

namespace Tests\Support;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Real HTTP, session/CSRF and Bearer checks against a disposable commerce fixture. */
final class PortfolioScenario
{
    public static function run(PortfolioDemo $demo): array
    {
        $checks = [];
        $check = static function (bool $condition, string $name) use (&$checks): void {
            if (! $condition) {
                throw new \RuntimeException('Demo check failed: '.$name);
            }
            $checks[] = $name;
        };
        $client = $demo->client(['headers' => ['Accept' => 'application/json']]);
        $check($client->get('/api/home')->getStatusCode() === 200, 'home_http');
        $check($client->get('/api/products/p7-phone-1')->getStatusCode() === 200, 'product_http');
        $check($client->get('/api/provinces')->getStatusCode() === 200, 'synthetic_addresses');
        $check($client->get('/api/auth/google/redirect')->getStatusCode() === 503, 'provider_blocked');
        $check($client->get('/storage/private.txt')->getStatusCode() === 404, 'source_uploads_blocked');
        $tokens = [];
        foreach (['buyer', 'other'] as $name) {
            $response = $client->post('/api/login', ['json' => ['email' => $name.'@example.test', 'password' => PortfolioDemo::PASSWORD]]);
            $body = json_decode($response->getBody(), true);
            $check($response->getStatusCode() === 200 && isset($body['token']), 'bearer_login_'.$name);
            $tokens[$name] = $body['token'];
        }
        $buyer = $demo->client(['headers' => ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$tokens['buyer']]]);
        $response = $buyer->post('/api/cart/add', ['json' => ['product_id' => 1000001, 'variant_id' => 2000001, 'quantity' => 1]]);
        $check($response->getStatusCode() === 200, 'cart_add');
        $payload = ['idempotency_key' => (string) Str::uuid(), 'payment_method' => 'cod',
            'items' => [['product_id' => 1000001, 'variant_id' => 2000001, 'quantity' => 1, 'price' => '1']],
            'fullname' => 'Synthetic Buyer', 'phone' => '0000000000', 'address' => 'Synthetic address',
            'ward' => 'Demo Ward', 'district' => 'Demo District', 'province' => 'Demo Province'];
        $response = $buyer->post('/api/checkout', ['json' => $payload]);
        $check(in_array($response->getStatusCode(), [200, 201], true), 'checkout_http');
        $order = DB::table('orders')->sole();
        $check((int) $order->total_price === 25000000, 'server_price_ignores_forgery');
        $replay = $buyer->post('/api/checkout', ['json' => $payload]);
        $check(in_array($replay->getStatusCode(), [200, 201], true) && DB::table('orders')->count() === 1, 'checkout_replay');
        $other = $demo->client(['headers' => ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$tokens['other']]]);
        $check($other->get('/api/orders/'.$order->id)->getStatusCode() === 404, 'order_ownership');
        $admin = $demo->client(['cookies' => new CookieJar]);
        $login = (string) $admin->get('/login')->getBody();
        preg_match('/name="_token"\s+value="([^"]+)"/', $login, $match);
        $check(isset($match[1]), 'session_csrf_form');
        $response = $admin->post('/login', ['form_params' => ['_token' => $match[1], 'email' => 'admin@example.test', 'password' => PortfolioDemo::PASSWORD]]);
        $check($response->getStatusCode() === 302 && $admin->get('/admin')->getStatusCode() === 200, 'admin_session_login');
        $page = (string) $admin->get('/admin/orders/'.$order->id)->getBody();
        preg_match('/name="csrf-token"\s+content="([^"]+)"/', $page, $csrf);
        if (! isset($csrf[1])) {
            preg_match('/name="_token"\s+value="([^"]+)"/', $page, $csrf);
        }
        $check(isset($csrf[1]), 'admin_csrf_token');
        $headers = ['Accept' => 'application/json', 'X-CSRF-TOKEN' => $csrf[1]];
        $check($admin->patch('/admin/orders/'.$order->id, ['headers' => ['Accept' => 'application/json'],
            'json' => ['status' => 'confirmed']])->getStatusCode() === 419, 'missing_csrf_rejected');
        $stock = static fn () => DB::table('inventory')->where('product_variant_id', 2000001)->where('warehouse_id', 1)->first();
        foreach (['confirmed', 'shipping', 'completed'] as $status) {
            $key = (string) Str::uuid();
            $options = ['headers' => $headers, 'json' => ['status' => $status, 'operation_key' => $key]];
            $response = $admin->patch('/admin/orders/'.$order->id, $options);
            $check($response->getStatusCode() === 302, 'transition_'.$status);
            if ($status === 'confirmed') {
                $check((int) $stock()->quantity === 10 && (int) $stock()->reserved_quantity === 1, 'confirmed_reserves_only');
            }
            if ($status === 'shipping') {
                $replay = $admin->patch('/admin/orders/'.$order->id, $options);
                $check($replay->getStatusCode() === 302 && (int) $stock()->quantity === 9 && (int) $stock()->reserved_quantity === 0, 'shipping_replay_no_double_debit');
            }
        }
        $check(DB::table('orders')->value('payment_status') === 'unpaid', 'completed_is_not_cod_paid');
        $money = ['headers' => $headers, 'json' => ['action' => 'collect_cod', 'amount' => 25000000,
            'reference' => 'DEMO-COD-001', 'operation_key' => (string) Str::uuid()]];
        $response = $admin->post('/admin/orders/'.$order->id.'/money', $money);
        $check($response->getStatusCode() === 200, 'cod_reconciliation');
        $replay = $admin->post('/admin/orders/'.$order->id.'/money', $money);
        $check($replay->getStatusCode() === 200 && DB::table('orders')->value('payment_status') === 'paid' && DB::table('payment_adjustments')->count() === 1, 'cod_replay_no_double_collection');

        return ['checks' => $checks, 'order' => ['status' => 'completed', 'payment_status' => 'paid', 'total_vnd' => 25000000],
            'stock' => ['physical' => (int) $stock()->quantity, 'reserved' => (int) $stock()->reserved_quantity,
                'available' => (int) $stock()->available_quantity], 'synthetic_only' => true];
    }
}
