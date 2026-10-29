<?php

// Explicit deployment command, never loaded by PHPUnit. No historical stock repair.
require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

if ($argc !== 2 || ! in_array($argv[1], ['--dry-run', '--apply'], true)) {
    fwrite(STDERR, "Usage: php tests/Support/cutover-p4.php --dry-run|--apply\n");
    exit(2);
}
$startedMaintenance = false;
try {
    $root = dirname(__DIR__, 2);
    $envText = file_get_contents($root.'/.env');
    $settings = Dotenv\Dotenv::parse($envText);
    if (is_file($root.'/bootstrap/cache/config.php') || ! empty($settings['APP_CONFIG_CACHE']) || ! empty($settings['DB_URL'])) {
        throw new RuntimeException('Cached/custom configuration requires a reviewed deployment adapter.');
    }
    $sandbox = new Tests\Support\DatabaseSandbox;
    $source = $sandbox->source();
    $receipt = json_decode(file_get_contents($root.'/tests/.runtime/p4-rollout.json'), true, flags: JSON_THROW_ON_ERROR);
    if (empty($receipt['application_applied']) || empty($receipt['application_business_fingerprints_preserved'])
        || ! hash_equals($receipt['source_identity_sha256'], $sandbox->sourceIdentityHash())) {
        throw new RuntimeException('P4 schema deployment receipt is required.');
    }
    foreach ($receipt['migration_sha256'] as $file => $hash) {
        if (! hash_equals($hash, hash_file('sha256', $root.'/database/migrations/'.$file))) {
            throw new RuntimeException('Applied migration was modified after deployment.');
        }
    }
    $app = require $root.'/bootstrap/app.php';
    $app->afterBootstrapping(LoadConfiguration::class, static function ($app) {
        $app['config']->set(['telescope.enabled' => false, 'inventory.enabled' => false, 'ordering.transitions_enabled' => false]);
    });
    $app->make(Kernel::class)->bootstrap();
    if (DB::connection()->getDatabaseName() !== $settings['DB_DATABASE'] || app()->environment('testing')
        || DB::selectOne('SELECT @@server_uuid AS server_identity')->server_identity !== $source->query('SELECT @@server_uuid')->fetchColumn()) {
        throw new RuntimeException('Deployment connection/environment mismatch.');
    }
    $fingerprint = static function () use ($source): array {
        $result = [];
        foreach (['users', 'orders', 'order_items', 'inventory', 'inventory_transactions', 'carts', 'cart_items', 'product_variants', 'payment_attempts', 'payment_events', 'order_transitions'] as $table) {
            $rows = $source->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
            $result[$table] = ['count' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
        }

        return $result;
    };
    $backfill = app(App\Services\Inventory\LegacyReservationBackfill::class);
    if ($argv[1] === '--dry-run') {
        echo json_encode($backfill->run(false), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
        exit(0);
    }
    $oldFlags = Dotenv\Dotenv::parse($envText);
    foreach (['INVENTORY_PROTOCOL_ENABLED', 'ORDER_TRANSITIONS_ENABLED'] as $key) {
        if (filter_var($oldFlags[$key] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('Initial cutover refuses already enabled writers; inspect previous receipt.');
        }
    }
    $directory = $root.'/storage/app/private/schema-backups';
    $envBackup = $directory.'/env-before-p4-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(6)).'.txt';
    if (file_put_contents($envBackup, $envText, LOCK_EX) !== strlen($envText)) {
        throw new RuntimeException('Private environment backup failed.');
    }
    $alreadyDown = $app->isDownForMaintenance();
    if (! $alreadyDown) {
        if (Artisan::call('down', ['--retry' => 60]) !== 0) {
            throw new RuntimeException('Maintenance mode failed.');
        }
        $startedMaintenance = true;
    }
    $before = $fingerprint();
    $classification = $backfill->run(true);
    $after = $fingerprint();
    if ($before !== $after) {
        throw new RuntimeException('Historical business rows changed; keep writers stopped and inspect.');
    }
    $reconciliation = app(App\Services\Inventory\ReconcileInventory::class)->report();
    if ($reconciliation['unexplained'] !== []) {
        throw new RuntimeException('Unclassified drift blocks cutover.');
    }
    if (file_get_contents($root.'/.env') !== $envText) {
        throw new RuntimeException('Environment changed concurrently.');
    }
    $newEnv = $envText;
    // New online payments remain disabled until merchant/public IPN verification.
    foreach (['INVENTORY_PROTOCOL_ENABLED' => 'true', 'ORDER_TRANSITIONS_ENABLED' => 'true', 'VNPAY_ENABLED' => 'false'] as $key => $value) {
        if (preg_match('/^'.preg_quote($key, '/').'=/m', $newEnv)) {
            $newEnv = preg_replace('/^'.preg_quote($key, '/').'=[^\r\n]*/m', $key.'='.$value, $newEnv);
        } else {
            $newEnv = rtrim($newEnv, "\r\n")."\n".$key.'='.$value."\n";
        }
    }
    $stagedEnv = $directory.'/.env-p4-'.bin2hex(random_bytes(6)).'.tmp';
    if (file_put_contents($stagedEnv, $newEnv, LOCK_EX) !== strlen($newEnv) || ! rename($stagedEnv, $root.'/.env')) {
        throw new RuntimeException('Environment write failed; keep maintenance mode.');
    }
    $report = ['recorded_at' => gmdate(DATE_ATOM), 'source_identity_sha256' => $sandbox->sourceIdentityHash(),
        'migration_count' => (int) $source->query('SELECT COUNT(*) FROM migrations')->fetchColumn(),
        'historical_business_rows_preserved' => true, 'business_fingerprints' => $after,
        'classification' => $classification, 'reconciliation' => $reconciliation,
        'enabled' => ['inventory_protocol' => true, 'order_transitions' => true, 'new_vnpay_payments' => false],
        'maintenance_was_already_active' => $alreadyDown];
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    if (file_put_contents($root.'/docs/architecture/p4-cutover-report.json', $json, LOCK_EX) !== strlen($json)) {
        throw new RuntimeException('Cutover report write failed.');
    }
    if ($startedMaintenance) {
        if (Artisan::call('up') !== 0) {
            throw new RuntimeException('Restore serving state failed.');
        }
        $startedMaintenance = false;
    }
    echo 'Metadata backfill and reconciliation passed. Historical rows preserved. P4 gates enabled; new VNPay payments disabled.'."\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'P4 cutover stopped: '.$exception::class.' / '.$exception->getCode().'; details redacted. '
        .($startedMaintenance ? 'Maintenance mode retained; inspect before re-enabling writers.' : 'Inspect private rollout receipt before retry.')."\n");
    exit(1);
}
