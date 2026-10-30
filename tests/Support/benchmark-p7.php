<?php

require __DIR__.'/../../vendor/autoload.php';

if ($argc !== 2 || ! in_array($argv[1], ['before', 'after'], true)) {
    fwrite(STDERR, "Usage: php tests/Support/benchmark-p7.php before|after\n");
    exit(2);
}
$sandbox = null;
try {
    putenv('P0_DB_MODE=disabled');
    Tests\Support\TestApplication::create();
    $sandbox = new Tests\Support\P7Sandbox;
    $sandbox->catalog();
    $run = static function (array $connection, string $workload): array {
        $process = new Symfony\Component\Process\Process([PHP_BINARY, '-d', 'memory_limit=1024M', __DIR__.'/p7-benchmark-worker.php'], dirname(__DIR__, 2));
        // Credentials are passed on stdin only and never saved in the public artifact.
        $process->setInput(json_encode(compact('connection', 'workload'), JSON_THROW_ON_ERROR))->setTimeout(300);
        if ($process->run() !== 0) {
            throw new RuntimeException('Benchmark subprocess failed.');
        }

        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    };
    $results = ['detail' => $run($sandbox->connection, 'detail'), 'home' => $run($sandbox->connection, 'home')];
    $sandbox->close();
    $sandbox = new Tests\Support\P7Sandbox;
    $sandbox->catalog(6667, 3, 5, 100000);
    $results['csv'] = $run($sandbox->connection, 'csv');
    $file = dirname(__DIR__, 2).'/docs/architecture/p7-performance-'.$argv[1].'.json';
    $report = ['phase' => 'P7', 'version' => $argv[1], 'recorded_at_utc' => gmdate(DATE_ATOM), 'php' => PHP_VERSION,
        'mysql' => Illuminate\Support\Facades\DB::selectOne('SELECT VERSION() AS version')->version,
        'method' => 'Fresh subprocess per workload; controller boundary, cold array cache per iteration; excludes application boot/seeding, includes SQL and response/CSV consumption. Local diagnostic, not an HTTP/load SLO. CSV uses a bounded output sink, no output accumulation.',
        'dataset' => ['products' => 1000, 'variants_per_product' => 3, 'warehouses' => 5, 'inventory_rows' => 15000,
            'csv_products' => 6667, 'csv_variants' => 20001, 'csv_inventory_rows' => 100000, 'fixture_version' => 1], 'results' => $results];
    file_put_contents($file, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    echo 'P7 '.$argv[1]." benchmark recorded (synthetic schemas only).\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'P7 benchmark stopped: '.$error::class.' / '.$error->getCode()."; details redacted.\n");
    exit(1);
} finally {
    $sandbox?->close();
}
