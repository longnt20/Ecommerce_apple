<?php

require __DIR__.'/../../vendor/autoload.php';

try {
    $sandbox = new Tests\Support\DatabaseSandbox;
    $result = [];
    foreach (['application' => $sandbox->source(), 'synthetic_test' => $sandbox->connectSandbox('apple_project_p0_tests')] as $label => $connection) {
        $connection->exec('START TRANSACTION READ ONLY');
        try {
            $result[$label]['migrations'] = (int) $connection->query('SELECT COUNT(*) FROM migrations')->fetchColumn();
            $result[$label]['payment_tables'] = (int) $connection->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('payment_attempts', 'payment_events')")->fetchColumn();
            foreach (['users', 'orders', 'order_items', 'inventory', 'inventory_transactions'] as $table) {
                $result[$label]['rows'][$table] = (int) $connection->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
            }
            if ($label === 'synthetic_test') {
                foreach (['payment_attempts', 'payment_events'] as $table) {
                    $result[$label]['rows'][$table] = (int) $connection->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
                }
            }
        } finally {
            $connection->rollBack();
        }
    }
    echo json_encode($result, JSON_PRETTY_PRINT)."\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Read-only inspection failed: '.$exception::class."; details redacted.\n");
    exit(1);
}
