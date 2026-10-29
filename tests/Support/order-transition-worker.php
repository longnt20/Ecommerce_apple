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
    config(['database.connections.p3_worker' => $connection, 'ordering.transitions_enabled' => true,
        'vnpay.vnp_tmn_code' => 'P3MERCHANT', 'vnpay.vnp_hash_secret' => 'synthetic-p3-secret']);
    Illuminate\Support\Facades\DB::setDefaultConnection('p3_worker');
    // Read a stale model before the barrier on purpose: the service must re-read it.
    $order = App\Models\Order::findOrFail($input['order_id']);
    echo "READY\n";
    flush();
    if (trim(fgets(STDIN)) !== 'GO') {
        throw new RuntimeException('Missing start barrier.');
    }
    if ($input['action'] === 'payment') {
        $result = app(App\Services\Payments\PaymentService::class)->notify($input['query']);
        echo json_encode(['result' => 'payment', 'ack' => $result['RspCode']])."\n";
    } else {
        try {
            $result = app(App\Services\OrderService::class)->changeStatus($order, $input['action'], 910002, $input['operation']);
            echo json_encode(['result' => 'ok', 'transition' => $result->id])."\n";
        } catch (App\Exceptions\OrderTransitionConflict $exception) {
            echo json_encode(['result' => 'conflict', 'code' => $exception->reasonCode])."\n";
        }
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class."; details redacted\n");
    exit(1);
}
