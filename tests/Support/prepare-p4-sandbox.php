<?php

require __DIR__.'/../../vendor/autoload.php';

if ($argc !== 2 || $argv[1] !== '--sandbox-only') {
    fwrite(STDERR, "Usage: php tests/Support/prepare-p4-sandbox.php --sandbox-only\n");
    exit(2);
}
try {
    putenv('P0_DB_MODE=disabled');
    Tests\Support\TestApplication::create();
    $sandbox = new Tests\Support\DatabaseSandbox;
    config(['database.connections.p4_provision' => $sandbox->connectionConfig('apple_project_p0_tests')]);
    Illuminate\Support\Facades\DB::setDefaultConnection('p4_provision');
    foreach (['2026_10_28_000001_reconcile_application_schema.php', '2026_10_29_000001_create_verified_payments.php', '2026_10_29_000002_create_order_transitions.php', '2026_10_29_000003_create_inventory_protocol.php', '2026_10_29_000004_create_inventory_legacy_holds.php'] as $file) {
        if (Illuminate\Support\Facades\Artisan::call('migrate', ['--database' => 'p4_provision', '--path' => 'database/migrations/'.$file, '--force' => true, '--no-interaction' => true]) !== 0) {
            throw new RuntimeException('P4 sandbox migration failed.');
        }
    }
    echo "P4 schema provisioned on synthetic sandbox only.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'P4 preparation failed: '.$exception::class.' / '.$exception->getCode()."; details redacted.\n");
    exit(1);
}
