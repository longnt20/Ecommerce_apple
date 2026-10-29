<?php

require __DIR__.'/../../vendor/autoload.php';

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    $connection = $input['connection'];
    if (! preg_match('/^apple_project_p1_fresh_[a-f0-9]{12}$/D', $connection['database'])
        || ! in_array($connection['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
        throw new RuntimeException('Worker refuses non-sandbox connection.');
    }
    putenv('P0_DB_MODE=disabled');
    Tests\Support\TestApplication::create();
    config(['database.connections.p4_worker' => $connection, 'ordering.transitions_enabled' => true, 'inventory.enabled' => true,
        'vnpay.enabled' => true, 'vnpay.vnp_tmn_code' => 'P4MERCHANT', 'vnpay.vnp_hash_secret' => 'synthetic-p4-secret']);
    Illuminate\Support\Facades\DB::setDefaultConnection('p4_worker');
    if (isset($input['now'])) {
        Illuminate\Support\Carbon::setTestNow($input['now']);
    }
    echo "READY\n";
    flush();
    if (trim(fgets(STDIN)) !== 'GO') {
        throw new RuntimeException('Missing start barrier.');
    }
    try {
        $action = $input['action'];
        if ($action === 'checkout') {
            $order = app(App\Services\Ordering\PlaceOrder::class)->place($input['payload'], $input['actor'] ?? 910001);
            $result = ['order_id' => $order->id];
        } elseif ($action === 'expiry') {
            $result = ['expired' => app(App\Services\Inventory\ExpireReservations::class)->expire($input['order_id'])];
        } elseif ($action === 'payment') {
            $result = app(App\Services\Payments\PaymentService::class)->notify($input['query']);
        } elseif ($action === 'confirmed') {
            $transition = app(App\Services\OrderService::class)->changeStatus(App\Models\Order::findOrFail($input['order_id']), 'confirmed', 910002, $input['key']);
            $result = ['transition' => $transition->id];
        } else {
            if ($action === 'kill-import') {
                Illuminate\Support\Facades\DB::beginTransaction();
                $action = 'import';
            }
            $result = app(App\Services\Inventory\StockWriter::class)->admin($action, 910002, $input['key'], $input['payload']);
            if ($input['action'] === 'kill-import') {
                echo "MUTATED\n";
                flush();
                // Parent kills this process while transaction is still open.
                fgets(STDIN);
                throw new RuntimeException('Killed-worker test unexpectedly resumed.');
            }
        }
        echo json_encode(['result' => 'ok', 'data' => $result], JSON_THROW_ON_ERROR)."\n";
    } catch (App\Exceptions\InventoryConflict|App\Exceptions\OrderTransitionConflict $exception) {
        echo json_encode(['result' => 'conflict', 'code' => $exception->reasonCode])."\n";
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class."; details redacted\n");
    exit(1);
}
