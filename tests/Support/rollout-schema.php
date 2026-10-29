<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Support\DatabaseSandbox;

require __DIR__.'/../../vendor/autoload.php';

$manifests = [
    'p2-p3' => ['2026_10_29_000001_create_verified_payments.php', '2026_10_29_000002_create_order_transitions.php'],
    'p4' => ['2026_10_29_000003_create_inventory_protocol.php', '2026_10_29_000004_create_inventory_legacy_holds.php'],
];
if ($argc !== 3 || ! isset($manifests[$argv[1]]) || ! in_array($argv[2], ['--rehearse', '--apply'], true)) {
    fwrite(STDERR, "Usage: php tests/Support/rollout-schema.php p2-p3|p4 --rehearse|--apply\n");
    exit(2);
}
$sandbox = null;
try {
    $root = dirname(__DIR__, 2);
    $phase = $argv[1];
    $settings = Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
    if (is_file($root.'/bootstrap/cache/config.php') || ! empty($settings['APP_CONFIG_CACHE']) || ! empty($settings['DB_URL'])) {
        throw new RuntimeException('Cached/custom configuration requires a reviewed deployment adapter.');
    }
    $sandbox = new DatabaseSandbox;
    $source = $sandbox->source();
    $files = $manifests[$phase];
    $hashes = [];
    foreach ($files as $file) {
        $hashes[$file] = hash_file('sha256', $root.'/database/migrations/'.$file);
    }
    $fingerprint = static function (PDO $connection, array $projection = []): array {
        $result = [];
        foreach (['users', 'orders', 'order_items', 'inventory', 'inventory_transactions', 'carts', 'cart_items', 'product_variants', 'payment_attempts', 'payment_events', 'order_transitions', 'inventory_operations', 'inventory_reservations', 'inventory_reservation_items', 'inventory_discrepancies', 'inventory_legacy_holds'] as $table) {
            $query = $connection->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
            $query->execute([$table]);
            if ((int) $query->fetchColumn() !== 0) {
                $columns = $projection[$table]['columns'] ?? $connection->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(PDO::FETCH_COLUMN);
                foreach ($columns as $column) {
                    if (! preg_match('/^[a-z_]+$/D', $column)) {
                        throw new RuntimeException('Unexpected column identifier.');
                    }
                }
                $rows = $connection->query('SELECT `'.implode('`, `', $columns).'` FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
                $result[$table] = ['columns' => $columns, 'count' => count($rows), 'sha256' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
            }
        }

        return $result;
    };
    $receiptPath = $root.'/tests/.runtime/'.$phase.'-rollout.json';
    if (! is_dir(dirname($receiptPath))) {
        mkdir(dirname($receiptPath), 0700, true);
    }
    if ($argv[2] === '--rehearse') {
        foreach ([['TRIGGERS', 'TRIGGER_SCHEMA'], ['ROUTINES', 'ROUTINE_SCHEMA'], ['EVENTS', 'EVENT_SCHEMA']] as [$table, $column]) {
            if ((int) $source->query('SELECT COUNT(*) FROM information_schema.'.$table.' WHERE '.$column.' = DATABASE()')->fetchColumn() !== 0) {
                throw new RuntimeException('Stored programs need a separate reviewed backup/restore plan.');
            }
        }
        $mysql = glob(dirname(PHP_BINARY, 3).'/mysql/*/bin/mysql.exe')[0] ?? null;
        $dump = glob(dirname(PHP_BINARY, 3).'/mysql/*/bin/mysqldump.exe')[0] ?? null;
        if (! $mysql || ! $dump) {
            throw new RuntimeException('Installed MySQL clients are required.');
        }
        $directory = $root.'/storage/app/private/schema-backups';
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        if (disk_free_space($directory) < 2 * 1024 * 1024 * 1024) {
            throw new RuntimeException('At least 2 GiB free space is required.');
        }
        $before = $fingerprint($source);
        $schema = hash('sha256', $sandbox->schemaSnapshot());
        $backup = $directory.'/before-'.$phase.'-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(6)).'.sql';
        $connection = ['--host='.$settings['DB_HOST'], '--port='.$settings['DB_PORT'], '--user='.$settings['DB_USERNAME']];
        $environment = ['MYSQL_PWD' => $settings['DB_PASSWORD'] ?? ''];
        $process = new Process(array_merge([$dump], $connection, ['--single-transaction', '--skip-lock-tables', '--hex-blob', '--set-gtid-purged=OFF', '--column-statistics=0', '--no-tablespaces', '--result-file='.$backup, $settings['DB_DATABASE']]), $root, $environment);
        if ($process->setTimeout(900)->run() !== 0 || ! is_file($backup) || filesize($backup) === 0) {
            throw new RuntimeException('Full backup failed.');
        }
        echo 'Full backup complete ('.filesize($backup)." bytes); restoring disposable clone.\n";
        $stream = fopen($backup, 'rb');
        while (($line = fgets($stream)) !== false) {
            if (preg_match('/^\s*(?:USE\s|CREATE\s+DATABASE\b|DROP\s+DATABASE\b)/i', $line)) {
                throw new RuntimeException('Backup database-selection statement rejected.');
            }
        }
        rewind($stream);
        $name = 'apple_project_p1_restore_'.bin2hex(random_bytes(6));
        $clone = $sandbox->create($name);
        $restore = new Process(array_merge([$mysql], $connection, ['--default-character-set=utf8mb4', $name]), $root, $environment);
        if ($restore->setInput($stream)->setTimeout(900)->run() !== 0) {
            throw new RuntimeException('Full restore failed.');
        }
        fclose($stream);
        if ($before !== $fingerprint($clone) || $before !== $fingerprint($source)) {
            throw new RuntimeException('Data changed during backup/restore; repeat rehearsal before application migration.');
        }
        putenv('P0_DB_MODE=disabled');
        Tests\Support\TestApplication::create();
        config(['database.connections.rollout_clone' => $sandbox->connectionConfig($name)]);
        DB::setDefaultConnection('rollout_clone');
        foreach ($files as $file) {
            if (Artisan::call('migrate', ['--database' => 'rollout_clone', '--path' => 'database/migrations/'.$file, '--force' => true, '--no-interaction' => true]) !== 0) {
                throw new RuntimeException('Clone migration failed.');
            }
        }
        $after = $fingerprint($clone, $before);
        foreach ($before as $table => $value) {
            if (($after[$table] ?? null) !== $value) {
                throw new RuntimeException('Clone migration changed existing business rows.');
            }
        }
        $receipt = ['phase' => $phase, 'source_identity_sha256' => $sandbox->sourceIdentityHash(), 'source_schema_sha256' => $schema,
            'migration_sha256' => $hashes, 'business_before' => $before,
            'backup' => str_replace('\\', '/', substr($backup, strlen($root) + 1)), 'backup_bytes' => filesize($backup), 'backup_sha256' => hash_file('sha256', $backup),
            'restore_verified' => true, 'clone_upgrade_verified' => true, 'application_applied' => false];
        file_put_contents($receiptPath, json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        DB::purge('rollout_clone');
        $sandbox->dropCreated();
        echo "Rehearsal passed; verified backup retained privately; application database unchanged.\n";
    } else {
        $receipt = json_decode(file_get_contents($receiptPath), true, flags: JSON_THROW_ON_ERROR);
        $backup = realpath($root.'/'.$receipt['backup']);
        $backupRoot = realpath($root.'/storage/app/private/schema-backups');
        if (empty($receipt['restore_verified']) || empty($receipt['clone_upgrade_verified']) || ! empty($receipt['application_applied'])
            || $receipt['phase'] !== $phase || $receipt['migration_sha256'] !== $hashes
            || ! hash_equals($receipt['source_identity_sha256'], $sandbox->sourceIdentityHash())
            || ! hash_equals($receipt['source_schema_sha256'], hash('sha256', $sandbox->schemaSnapshot()))
            || $receipt['business_before'] !== $fingerprint($source, $receipt['business_before'])
            || ! $backup || ! $backupRoot || ! str_starts_with($backup, $backupRoot.DIRECTORY_SEPARATOR)
            || ! hash_equals($receipt['backup_sha256'], hash_file('sha256', $backup))) {
            throw new RuntimeException('Verified receipt/source/schema/data/migration/backup mismatch; repeat rehearsal.');
        }
        $environment = ['APP_ENV' => $settings['APP_ENV'] ?? 'local', 'APP_CONFIG_CACHE' => 'bootstrap/cache/config.php', 'TELESCOPE_ENABLED' => 'false', 'DB_URL' => ''];
        foreach (['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
            $environment[$key] = $settings[$key] ?? '';
        }
        foreach ($files as $file) {
            $process = new Process([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction', '--path=database/migrations/'.$file], $root, $environment);
            if ($process->setTimeout(180)->run() !== 0) {
                throw new RuntimeException('Application migration failed; inspect privately, do not restore over newer transactions.');
            }
        }
        $after = $fingerprint($source, $receipt['business_before']);
        foreach ($receipt['business_before'] as $table => $value) {
            if (($after[$table] ?? null) !== $value) {
                throw new RuntimeException('Post-migration business rows changed; inspect concurrent writes before continuing.');
            }
        }
        $receipt['application_applied'] = true;
        $receipt['application_business_fingerprints_preserved'] = true;
        $receipt['application_migration_count'] = (int) $source->query('SELECT COUNT(*) FROM migrations')->fetchColumn();
        file_put_contents($receiptPath, json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        echo 'Application migrations applied; business fingerprints preserved; migration count '.$receipt['application_migration_count'].".\n";
    }
} catch (Throwable $exception) {
    if ($sandbox !== null) {
        $sandbox->dropCreated();
    }
    fwrite(STDERR, 'Schema rollout stopped: '.$exception::class.' / '.$exception->getCode()."; details redacted. Inspect private receipt before retry.\n");
    exit(1);
}
