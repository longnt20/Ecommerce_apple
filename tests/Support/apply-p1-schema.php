<?php

use Symfony\Component\Process\Process;
use Tests\Support\DatabaseSandbox;

require dirname(__DIR__, 2).'/vendor/autoload.php';

if ($argc !== 2 || $argv[1] !== '--application-from-env') {
    fwrite(STDERR, "Usage: php tests/Support/apply-p1-schema.php --application-from-env\nRequires a verified full backup/restore rehearsal. Runs only the P1 repair migration.\n");
    exit(2);
}

try {
    $root = dirname(__DIR__, 2);
    $receiptPath = $root.'/tests/.runtime/p1-rehearsal.json';
    $receipt = json_decode(file_get_contents($receiptPath), true, flags: JSON_THROW_ON_ERROR);
    if (empty($receipt['restore_verified']) || empty($receipt['upgrade_verified']) || ! empty($receipt['application_database_migrated'])) {
        throw new RuntimeException('A successful, not-yet-applied rehearsal receipt is required.');
    }
    $backup = realpath($root.'/'.$receipt['backup']);
    $backupRoot = realpath($root.'/storage/app/private/schema-backups');
    if ($backup === false || $backupRoot === false || ! str_starts_with($backup, $backupRoot.DIRECTORY_SEPARATOR)
        || ! hash_equals($receipt['backup_sha256'], hash_file('sha256', $backup))) {
        throw new RuntimeException('Verified backup is missing or changed.');
    }
    $settings = Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
    if (is_file($root.'/bootstrap/cache/config.php') || ! empty($settings['APP_CONFIG_CACHE'])) {
        throw new RuntimeException('Cached/custom application configuration must be reviewed before using this explicit .env deployment path.');
    }
    $sandbox = new DatabaseSandbox;
    if (! isset($receipt['source_identity_sha256'], $receipt['repair_sha256'])
        || ! hash_equals($receipt['source_identity_sha256'], $sandbox->sourceIdentityHash())
        || ! hash_equals($receipt['repair_sha256'], hash_file('sha256', $root.'/database/migrations/2026_10_28_000001_reconcile_application_schema.php'))) {
        throw new RuntimeException('The source endpoint or repair changed since rehearsal; repeat verification.');
    }
    $source = $sandbox->source();
    foreach ($receipt['row_counts'] as $table => $count) {
        if (! in_array($table, ['users', 'orders', 'order_items', 'inventory', 'inventory_transactions'], true)
            || (int) $source->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn() !== $count) {
            throw new RuntimeException('Business row counts changed since rehearsal; refresh backup/rehearsal first.');
        }
    }
    $fingerprints = [];
    foreach (['orders', 'order_items', 'inventory', 'inventory_transactions'] as $table) {
        $fingerprints[$table] = hash('sha256', json_encode($source->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)));
    }
    $users = $source->query('SELECT * FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as &$user) {
        if (trim($user['code'] ?? '') === '') {
            $user['code'] = 'legacy-user-'.$user['id'];
        }
        if ($user['status'] === 'block') {
            $user['status'] = 'blocked';
        }
    }
    unset($user);
    $environment = [
        'APP_ENV' => $settings['APP_ENV'] ?? 'local',
        'APP_CONFIG_CACHE' => 'bootstrap/cache/config.php',
    ];
    foreach (['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
        $environment[$key] = $settings[$key] ?? '';
    }
    $environment['DB_URL'] = '';
    $process = new Process([
        PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction',
        '--path=database/migrations/2026_10_28_000001_reconcile_application_schema.php',
    ], $root, $environment);
    $process->setTimeout(180);
    if ($process->run() !== 0) {
        throw new RuntimeException('Application migration failed. Inspect privately and use the forward-repair plan; do not restore over newer transactions.');
    }
    $receipt['application_database_migrated'] = true;
    file_put_contents($receiptPath, json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    foreach ($fingerprints as $table => $hash) {
        if (! hash_equals($hash, hash('sha256', json_encode($source->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC))))) {
            throw new RuntimeException('Post-migration business data differs. Migration is already applied; review concurrent changes before taking action.');
        }
    }
    if ($users !== $source->query('SELECT * FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)) {
        throw new RuntimeException('User rows differ beyond approved mappings; review required.');
    }
    $receipt['application_business_fingerprints_preserved'] = true;
    $receipt['application_users_match_approved_mapping'] = true;
    file_put_contents($receiptPath, json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "P1 repair applied to the .env application database. Business fingerprints and approved user mappings verified.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'P1 application step failed: '.get_class($exception).' / '.$exception->getCode().". Credentials and business data omitted; inspect local receipts before retrying.\n");
    exit(1);
}
