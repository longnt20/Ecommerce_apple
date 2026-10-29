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
    config(['database.connections.p2_worker' => $connection, 'vnpay.enabled' => true,
        'vnpay.vnp_tmn_code' => 'P2MERCHANT', 'vnpay.vnp_hash_secret' => 'synthetic-private-key']);
    Illuminate\Support\Facades\DB::setDefaultConnection('p2_worker');
    echo "READY\n";
    flush();
    if (trim(fgets(STDIN)) !== 'GO') {
        throw new RuntimeException('Missing start barrier.');
    }
    echo json_encode(app(App\Services\Payments\PaymentService::class)->notify($input['query']))."\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class."; details redacted\n");
    exit(1);
}
