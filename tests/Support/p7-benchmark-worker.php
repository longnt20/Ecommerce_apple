<?php

require __DIR__.'/../../vendor/autoload.php';

try {
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    $connection = $input['connection'];
    if (! preg_match('/^apple_project_p1_fresh_[a-f0-9]{12}$/D', $connection['database'])
        || ! in_array($connection['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
        throw new RuntimeException('Benchmark refuses non-sandbox connection.');
    }
    putenv('P0_DB_MODE=disabled');
    Tests\Support\TestApplication::create();
    config(['database.connections.p7_bench' => $connection]);
    Illuminate\Support\Facades\DB::setDefaultConnection('p7_bench');
    Illuminate\Support\Carbon::setTestNow('2026-10-30 06:00:00');
    auth()->setUser(App\Models\User::findOrFail(910002));
    $queries = $sqlMs = 0;
    Illuminate\Support\Facades\DB::listen(function ($event) use (&$queries, &$sqlMs) {
        $queries++;
        $sqlMs += $event->time;
    });
    $durations = $counts = $peaks = $sqlTimes = [];
    $hash = null;
    $bytes = 0;
    $workload = $input['workload'];
    $runs = $workload === 'csv' ? 3 : 30;
    for ($iteration = 0; $iteration < $runs; $iteration++) {
        Illuminate\Support\Facades\Cache::flush();
        app()->forgetScopedInstances(); // Each controller iteration represents a new request.
        gc_collect_cycles();
        memory_reset_peak_usage();
        $queries = $sqlMs = 0;
        $start = hrtime(true);
        if ($workload === 'detail') {
            $response = app(App\Http\Controllers\Api\HomeController::class)->productDetail(Illuminate\Http\Request::create('/api/products/p7-phone-1'), 'p7-phone-1');
            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Detail failed.');
            }
            $body = $response->getContent();
            $hash = hash('sha256', $body);
            $bytes = strlen($body);
        } elseif ($workload === 'home') {
            $response = app(App\Http\Controllers\Api\HomeController::class)->homeProducts();
            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Home failed.');
            }
            $hash = hash('sha256', $response->getContent());
            $bytes = strlen($response->getContent());
        } elseif ($workload === 'csv') {
            $digest = hash_init('sha256');
            $bytes = 0;
            ob_start(function ($chunk) use ($digest, &$bytes) {
                hash_update($digest, $chunk);
                $bytes += strlen($chunk);

                return '';
            }, 8192);
            try {
                app(App\Http\Controllers\Admin\InventoryController::class)->exportExcel(Illuminate\Http\Request::create('/admin/inventory/reports/export'))->sendContent();
            } finally {
                ob_end_flush();
            }
            $hash = hash_final($digest);
        } else {
            throw new RuntimeException('Unknown workload.');
        }
        $durations[] = (hrtime(true) - $start) / 1e6;
        $counts[] = $queries;
        $sqlTimes[] = $sqlMs;
        $peaks[] = memory_get_peak_usage(true);
        unset($response, $body);
    }
    sort($durations);
    echo json_encode(['samples' => $runs, 'query_count_max' => max($counts),
        'p95_ms' => round($durations[(int) ceil($runs * .95) - 1], 3), 'median_ms' => round($durations[(int) floor($runs / 2)], 3),
        'sql_time_ms_max' => round(max($sqlTimes), 3), 'peak_memory_bytes' => max($peaks), 'response_sha256' => $hash, 'response_bytes' => $bytes], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    fwrite(STDERR, 'P7 benchmark failed: '.$error::class.' / '.$error->getCode()."; details redacted.\n");
    exit(1);
}
