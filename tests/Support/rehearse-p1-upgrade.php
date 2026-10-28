<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Support\DatabaseSandbox;

require dirname(__DIR__).'/bootstrap.php';

if ($argc !== 2 || $argv[1] !== '--backup-and-rehearse-from-env') {
    fwrite(STDERR, "Usage: php tests/Support/rehearse-p1-upgrade.php --backup-and-rehearse-from-env\n");
    exit(2);
}

$sandbox = null;
try {
    $root = dirname(__DIR__, 2);
    $settings = Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
    $sandbox = new DatabaseSandbox;
    foreach ([['TRIGGERS', 'TRIGGER_SCHEMA'], ['ROUTINES', 'ROUTINE_SCHEMA'], ['EVENTS', 'EVENT_SCHEMA']] as [$table, $column]) {
        $query = $sandbox->source()->query('SELECT COUNT(*) FROM information_schema.'.$table.' WHERE '.$column.' = DATABASE()');
        if ((int) $query->fetchColumn() !== 0) {
            throw new RuntimeException('Stored programs need a reviewed restore plan before cloning.');
        }
    }
    $mysql = glob(dirname(PHP_BINARY, 3).'/mysql/*/bin/mysql.exe')[0] ?? null;
    $dump = glob(dirname(PHP_BINARY, 3).'/mysql/*/bin/mysqldump.exe')[0] ?? null;
    if ($mysql === null || $dump === null) {
        throw new RuntimeException('Installed MySQL clients are required for this rehearsal.');
    }
    $directory = $root.'/storage/app/private/schema-backups';
    if (! is_dir($directory)) {
        mkdir($directory, 0700, true);
    }
    if (disk_free_space($directory) < 2 * 1024 * 1024 * 1024) {
        throw new RuntimeException('At least 2 GiB free space is required before backup.');
    }
    $backup = $directory.'/before-p1-20261028-'.bin2hex(random_bytes(6)).'.sql';
    $connection = ['--host='.$settings['DB_HOST'], '--port='.$settings['DB_PORT'], '--user='.$settings['DB_USERNAME']];
    $environment = ['MYSQL_PWD' => $settings['DB_PASSWORD'] ?? ''];
    $process = new Process(array_merge([$dump], $connection, [
        '--single-transaction', '--skip-lock-tables', '--hex-blob', '--set-gtid-purged=OFF',
        '--column-statistics=0', '--no-tablespaces', '--result-file='.$backup, $settings['DB_DATABASE'],
    ]), $root, $environment);
    $process->setTimeout(600);
    if ($process->run() !== 0 || ! is_file($backup) || filesize($backup) === 0) {
        throw new RuntimeException('Backup failed; application migration must not run.');
    }
    echo 'Backup written ('.filesize($backup)." bytes); verifying restore on isolated database.\n";

    // Reject any database-selection statement before handing the dump to mysql.
    $stream = fopen($backup, 'rb');
    while (($line = fgets($stream)) !== false) {
        if (preg_match('/^\s*(?:USE\s|CREATE\s+DATABASE\b)/i', $line)) {
            throw new RuntimeException('Backup contains database selection; manual review required.');
        }
    }
    rewind($stream);
    $name = 'apple_project_p1_restore_'.bin2hex(random_bytes(6));
    $clone = $sandbox->create($name);
    $restore = new Process(array_merge([$mysql], $connection, ['--default-character-set=utf8mb4', $name]), $root, $environment);
    $restore->setInput($stream)->setTimeout(600);
    if ($restore->run() !== 0) {
        throw new RuntimeException('Restore failed; application migration must not run.');
    }
    fclose($stream);
    $before = [];
    foreach (['users', 'orders', 'order_items', 'inventory', 'inventory_transactions'] as $table) {
        $before[$table] = (int) $clone->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
    }
    $business = [];
    foreach (['orders', 'order_items', 'inventory', 'inventory_transactions'] as $table) {
        $business[$table] = hash('sha256', json_encode($clone->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)));
    }
    $emptyCodes = (int) $clone->query("SELECT COUNT(*) FROM users WHERE TRIM(COALESCE(code, '')) = ''")->fetchColumn();
    $app = Tests\Support\TestApplication::create();
    config(['database.connections.p1_rehearsal' => $sandbox->connectionConfig($name)]);
    if (Artisan::call('migrate', ['--database' => 'p1_rehearsal', '--path' => 'database/migrations/2026_10_28_000001_reconcile_application_schema.php', '--force' => true, '--no-interaction' => true]) !== 0) {
        throw new RuntimeException('Rehearsal migration failed.');
    }
    foreach ($before as $table => $count) {
        if ((int) $clone->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn() !== $count) {
            throw new RuntimeException('Rehearsal changed a row count unexpectedly.');
        }
    }
    foreach ($business as $table => $hash) {
        $after = hash('sha256', json_encode($clone->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)));
        if ($after !== $hash) {
            throw new RuntimeException('Rehearsal changed business rows unexpectedly.');
        }
    }
    $receipt = [
        'source_identity_sha256' => $sandbox->sourceIdentityHash(),
        'repair_sha256' => hash_file('sha256', $root.'/database/migrations/2026_10_28_000001_reconcile_application_schema.php'),
        'backup' => str_replace('\\', '/', substr($backup, strlen($root) + 1)),
        'backup_bytes' => filesize($backup), 'backup_sha256' => hash_file('sha256', $backup),
        'restore_verified' => true, 'upgrade_verified' => true, 'business_hashes_preserved' => true,
        'row_counts' => $before, 'empty_user_codes_mapped' => $emptyCodes,
        'application_database_migrated' => false,
    ];
    file_put_contents($root.'/tests/.runtime/p1-rehearsal.json', json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Restore and upgrade rehearsal passed; row counts and business fingerprints preserved.\n";
    DB::purge('p1_rehearsal');
    $sandbox->dropCreated();
} catch (Throwable $exception) {
    // Avoid printing process command lines, credentials or restored business data.
    fwrite(STDERR, 'P1 rehearsal failed: '.get_class($exception).' / '.$exception->getCode().". Review privately; no application migration was executed.\n");
    if ($sandbox !== null) {
        $sandbox->dropCreated();
    }
    exit(1);
}
