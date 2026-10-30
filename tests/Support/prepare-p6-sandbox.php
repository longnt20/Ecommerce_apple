<?php

// The P4 helper checks --sandbox-only, uses the allowlisted synthetic schema,
// and exits on failure. No application connection is installed in Laravel.
require __DIR__.'/prepare-p4-sandbox.php';

try {
    $file = '2026_10_30_000001_create_commerce_lifecycle.php';
    if (Illuminate\Support\Facades\Artisan::call('migrate', ['--database' => 'p4_provision', '--path' => 'database/migrations/'.$file, '--force' => true, '--no-interaction' => true]) !== 0) {
        throw new RuntimeException('P6 sandbox migration failed.');
    }
    // Verify re-entry/repair of interrupted additive DDL on this sandbox only.
    (require database_path('migrations/'.$file))->up();
    echo "P6 schema provisioned on synthetic sandbox only.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'P6 preparation stopped: '.$error::class.' / '.$error->getCode()."; details redacted.\n");
    exit(1);
}
