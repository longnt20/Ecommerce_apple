<?php

require __DIR__.'/../../vendor/autoload.php';

if ($argc !== 2 || $argv[1] !== '--sandbox-only') {
    fwrite(STDERR, "Usage: php tests/Support/prepare-p3-sandbox.php --sandbox-only\n");
    exit(2);
}
try {
    putenv('P0_DB_MODE=disabled');
    Tests\Support\TestApplication::create();
    $sandbox = new Tests\Support\DatabaseSandbox;
    config(['database.connections.p3_provision' => $sandbox->connectionConfig('apple_project_p0_tests')]);
    Illuminate\Support\Facades\DB::setDefaultConnection('p3_provision');
    foreach (['2026_10_28_000001_reconcile_application_schema.php', '2026_10_29_000001_create_verified_payments.php', '2026_10_29_000002_create_order_transitions.php'] as $file) {
        $exit = Illuminate\Support\Facades\Artisan::call('migrate', ['--database' => 'p3_provision',
            '--path' => 'database/migrations/'.$file, '--force' => true, '--no-interaction' => true]);
        if ($exit !== 0) {
            throw new RuntimeException('P3 sandbox migration failed.');
        }
    }
    echo "P3 migration applied to synthetic sandbox only; application database unchanged.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'P3 preparation failed: '.$exception::class.' / '.$exception->getCode()."; details redacted.\n");
    exit(1);
}
