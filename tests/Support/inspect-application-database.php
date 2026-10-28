<?php

// Standalone, explicit read-only inspection. Does not bootstrap Laravel or run providers.
require dirname(__DIR__, 2).'/vendor/autoload.php';

if ($argc !== 2 || $argv[1] !== '--application-read-only') {
    fwrite(STDERR, "Usage: php tests/Support/inspect-application-database.php --application-read-only\n");
    exit(2);
}

try {
    $root = dirname(__DIR__, 2);
    $settings = Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
    if (($settings['DB_CONNECTION'] ?? '') !== 'mysql' || ! empty($settings['DB_URL'])) {
        throw new RuntimeException('Only an explicit MySQL .env configuration without DB_URL is supported.');
    }

    foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE'] as $key) {
        if (! preg_match('/^[a-zA-Z0-9_.:-]+$/', (string) ($settings[$key] ?? ''))) {
            throw new RuntimeException('Unsupported database connection configuration.');
        }
    }

    $pdo = new PDO(
        'mysql:host='.$settings['DB_HOST'].';port='.$settings['DB_PORT'].';dbname='.$settings['DB_DATABASE'].';charset=utf8mb4',
        $settings['DB_USERNAME'] ?? '', $settings['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3],
    );
    $pdo->exec('START TRANSACTION READ ONLY');
    $runtime = $pdo->query('SELECT VERSION() AS version, @@session.transaction_isolation AS isolation_level, @@session.sql_mode AS sql_mode, @@session.transaction_read_only AS session_default_transaction_read_only')->fetch(PDO::FETCH_ASSOC);
    // The session variable is the default, not the access mode of START TRANSACTION READ ONLY.
    $runtime['requested_transaction_access_mode'] = 'READ ONLY';
    $tables = $pdo->query('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME')->fetchAll(PDO::FETCH_ASSOC);
    $migrations = $pdo->query('SELECT migration, batch FROM migrations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $columns = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND ((TABLE_NAME = 'users' AND COLUMN_NAME IN ('role','status','code')) OR (TABLE_NAME = 'product_variants' AND COLUMN_NAME IN ('thumbnail','thumnail')) OR (TABLE_NAME = 'inventory_transactions' AND COLUMN_NAME = 'type')) ORDER BY TABLE_NAME, ORDINAL_POSITION")->fetchAll(PDO::FETCH_ASSOC);
    $counts = [];
    foreach (['inventory', 'inventory_transactions', 'orders', 'order_items'] as $table) {
        $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
    }
    $identityHealth = [
        'empty_user_codes' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE TRIM(COALESCE(code, '')) = ''")->fetchColumn(),
        'duplicate_nonempty_code_groups' => (int) $pdo->query("SELECT COUNT(*) FROM (SELECT code FROM users WHERE TRIM(COALESCE(code, '')) <> '' GROUP BY code HAVING COUNT(*) > 1) duplicates")->fetchColumn(),
        'unique_code_indexes' => (int) $pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'code' AND NON_UNIQUE = 0")->fetchColumn(),
    ];
    $pdo->rollBack();

    echo json_encode([
        'source' => '.env-based native PDO read-only probe; cached Laravel config is not loaded',
        'runtime' => $runtime, 'tables' => $tables, 'migrations' => $migrations, 'selected_schema_columns' => $columns, 'row_counts' => $counts, 'identity_health' => $identityHealth,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Do not print exception text: connection failures may contain account details.
    fwrite(STDERR, 'Read-only inspection failed; exception type '.get_class($exception).', code '.$exception->getCode().'. No migration or data mutation was attempted.'.PHP_EOL);
    exit(1);
}
