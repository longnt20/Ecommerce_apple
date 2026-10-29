<?php

// Read-only application-data classification; no account, address or monetary details exported.
require __DIR__.'/../../vendor/autoload.php';

if ($argc > 2 || ($argc === 2 && $argv[1] !== '--write-report')) {
    fwrite(STDERR, "Usage: php tests/Support/audit-p3-legacy-orders.php [--write-report]\n");
    exit(2);
}
try {
    $sandbox = new Tests\Support\DatabaseSandbox;
    $connection = $sandbox->source();
    $policy = new App\Services\Ordering\OrderTransitionPolicy;
    $evidence = new App\Services\Ordering\OrderReservationEvidence;
    $result = ['observed_at_utc' => gmdate('c'), 'mode' => 'READ ONLY', 'orders' => [], 'counts' => []];
    $connection->exec('START TRANSACTION READ ONLY');
    try {
        $orders = $connection->query('SELECT id, warehouse_id, status, payment_status FROM orders ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $items = $connection->prepare('SELECT variant_id, quantity FROM order_items WHERE order_id = ?');
        $movements = $connection->prepare("SELECT warehouse_id, product_variant_id, type, quantity, before_quantity, after_quantity, status FROM inventory_transactions WHERE reference_type = 'order' AND reference_id = ?");
        $stock = $connection->prepare('SELECT quantity, reserved_quantity, available_quantity FROM inventory WHERE warehouse_id = ? AND product_variant_id = ?');
        foreach ($orders as $order) {
            $next = $policy->next($order['status']);
            $row = ['order_id' => (int) $order['id'], 'warehouse_id' => (int) $order['warehouse_id'], 'status' => $order['status'], 'next' => []];
            if ($next === []) {
                $row['classification'] = 'terminal_no_transition';
            } else {
                try {
                    $items->execute([$order['id']]);
                    $movements->execute([$order['id']]);
                    $allocation = $evidence->allocation($items->fetchAll(PDO::FETCH_ASSOC), $movements->fetchAll(PDO::FETCH_ASSOC), (int) $order['warehouse_id'], $order['status']);
                    foreach ($allocation as $variant => $quantity) {
                        $stock->execute([$order['warehouse_id'], $variant]);
                        $inventory = $stock->fetch(PDO::FETCH_ASSOC);
                        if (! $inventory) {
                            throw new App\Exceptions\OrderTransitionConflict('missing_inventory', 'Missing stock');
                        }
                        $evidence->assertBalance($inventory, $quantity, $order['status'] !== 'shipping');
                    }
                    $row['classification'] = 'evidence_valid_at_read_time';
                    $row['next'] = $next;
                    $row['paid_cancellation_requires_refund_review'] = $order['payment_status'] === 'paid' && in_array('cancelled', $next, true);
                } catch (App\Exceptions\OrderTransitionConflict $exception) {
                    $row['classification'] = $exception->reasonCode;
                }
            }
            $result['orders'][] = $row;
            $label = $row['classification'];
            $result['counts'][$label] = ($result['counts'][$label] ?? 0) + 1;
        }
        $result['application_migrations'] = (int) $connection->query('SELECT COUNT(*) FROM migrations')->fetchColumn();
        $result['application_transition_tables'] = (int) $connection->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_transitions'")->fetchColumn();
    } finally {
        $connection->rollBack();
    }
    $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($argc === 2) {
        $path = __DIR__.'/../../docs/architecture/p3-legacy-order-audit.json';
        file_put_contents($path, $json."\n", LOCK_EX);
    }
    echo $json."\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'P3 read-only audit failed: '.$exception::class.' / '.$exception->getCode()."; details redacted.\n");
    exit(1);
}
