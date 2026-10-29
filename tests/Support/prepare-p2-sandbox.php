<?php

// Explicit DDL provisioning of the allowlisted synthetic test schema only.
require __DIR__.'/../../vendor/autoload.php';

if ($argc !== 2 || $argv[1] !== '--sandbox-only') {
    fwrite(STDERR, "Usage: php tests/Support/prepare-p2-sandbox.php --sandbox-only\n");
    exit(2);
}
try {
    putenv('P0_DB_MODE=disabled');
    $app = Tests\Support\TestApplication::create();
    $sandbox = new Tests\Support\DatabaseSandbox;
    $name = 'apple_project_p0_tests';
    // connectionConfig refuses the application DB and all non-allowlisted schemas.
    config(['database.connections.p2_provision' => $sandbox->connectionConfig($name)]);
    Illuminate\Support\Facades\DB::setDefaultConnection('p2_provision');
    foreach (['2026_10_28_000001_reconcile_application_schema.php', '2026_10_29_000001_create_verified_payments.php'] as $file) {
        $exit = Illuminate\Support\Facades\Artisan::call('migrate', [
            '--database' => 'p2_provision', '--path' => 'database/migrations/'.$file,
            '--force' => true, '--no-interaction' => true,
        ]);
        if ($exit !== 0) {
            throw new RuntimeException('Sandbox migration failed.');
        }
    }
    echo "P2 schema applied to synthetic sandbox only; application database unchanged.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Sandbox preparation failed: '.$exception::class.' / '.$exception->getCode().' / '.($exception instanceof PDOException ? ($exception->errorInfo[1] ?? '') : '')."; credentials/SQL omitted.\n");
    exit(1);
}
