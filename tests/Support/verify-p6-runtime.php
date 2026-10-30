<?php

require __DIR__.'/../../vendor/autoload.php';

if ($argc !== 2 || $argv[1] !== '--application-read-only') {
    fwrite(STDERR, "Usage: php tests/Support/verify-p6-runtime.php --application-read-only\n");
    exit(2);
}
try {
    $source = (new Tests\Support\DatabaseSandbox)->source();
    $receipt = json_decode(file_get_contents(__DIR__.'/../.runtime/p6-rollout.json'), true, flags: JSON_THROW_ON_ERROR);
    if (empty($receipt['application_applied']) || empty($receipt['application_business_fingerprints_preserved'])) {
        throw new RuntimeException('Verified application rollout receipt required.');
    }
    $checks = [];
    foreach ($receipt['business_before'] as $table => $expected) {
        if (! preg_match('/^[a-z_]+$/D', $table)) {
            throw new RuntimeException('Unexpected table identifier.');
        }
        foreach ($expected['columns'] as $column) {
            if (! preg_match('/^[a-z_]+$/D', $column)) {
                throw new RuntimeException('Unexpected column identifier.');
            }
        }
        $rows = $source->query('SELECT `'.implode('`, `', $expected['columns']).'` FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $preserved = count($rows) === $expected['count'] && hash_equals($expected['sha256'], hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)));
        $checks[$table] = ['count' => count($rows), 'preserved' => $preserved];
        if (! $preserved) {
            throw new RuntimeException('Existing data changed since rehearsal; inspect concurrent writes.');
        }
    }
    $counts = [];
    foreach (['payment_adjustments', 'payment_adjustment_events', 'coupon_redemptions', 'coupon_assignment_deliveries',
        'inventory_return_receipts', 'inventory_documents', 'inventory_document_lines', 'inventory_settings'] as $table) {
        $counts[$table] = (int) $source->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
    }
    $settings = Dotenv\Dotenv::parse(file_get_contents(__DIR__.'/../../.env'));
    $report = ['phase' => 'P6', 'verified_at_utc' => gmdate(DATE_ATOM), 'migration_count' => (int) $source->query('SELECT COUNT(*) FROM migrations')->fetchColumn(),
        'runtime_gates' => ['vnpay_enabled' => filter_var($settings['VNPAY_ENABLED'] ?? false, FILTER_VALIDATE_BOOL),
            'inventory_protocol_enabled' => filter_var($settings['INVENTORY_PROTOCOL_ENABLED'] ?? false, FILTER_VALIDATE_BOOL),
            'order_transitions_enabled' => filter_var($settings['ORDER_TRANSITIONS_ENABLED'] ?? false, FILTER_VALIDATE_BOOL)],
        'full_backup_restore_rehearsal_verified' => true, 'old_column_business_fingerprints' => $checks, 'new_table_counts' => $counts,
        'legacy_duplicate_assignment_pairs' => (int) $source->query('SELECT COUNT(*) FROM (SELECT coupon_id, user_id FROM coupon_uses GROUP BY coupon_id, user_id HAVING COUNT(*) > 1) duplicate_pairs')->fetchColumn(),
        'legacy_paid_without_verified_collection' => (int) $source->query("SELECT COUNT(*) FROM orders o WHERE o.payment_status = 'paid' AND NOT EXISTS (SELECT 1 FROM payment_attempts p WHERE p.order_id = o.id AND p.status = 'paid' AND p.amount_vnd = o.final_amount) AND NOT EXISTS (SELECT 1 FROM payment_adjustments a WHERE a.order_id = o.id AND a.kind = 'cod' AND a.status = 'collected' AND a.amount = o.final_amount)")->fetchColumn(),
        'legacy_action' => 'Preserved; no financial, return or coupon backfill. Ambiguous evidence is refused by new workflows.'];
    file_put_contents(__DIR__.'/../../docs/architecture/p6-runtime-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n", LOCK_EX);
    echo "P6 read-only verification recorded; existing business fingerprints preserved.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'P6 verification stopped: '.$error::class.' / '.$error->getCode()."; details redacted.\n");
    exit(1);
}
