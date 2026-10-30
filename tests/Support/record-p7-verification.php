<?php

require __DIR__.'/../../vendor/autoload.php';

// Read-only source comparison against the private P6 rollout receipt; publish counts only.
$root = dirname(__DIR__, 2);
$junit = $root.'/tests/.runtime/p7-full-junit.xml';
if (! is_file($junit) || ! is_file($root.'/tests/.runtime/p6-rollout.json')) {
    fwrite(STDERR, "Full JUnit and private P6 receipt required; no acceptance evidence invented.\n");
    exit(2);
}
try {
    $xml = new DOMDocument;
    if (! $xml->load($junit, LIBXML_NONET)) {
        throw new RuntimeException('Invalid JUnit.');
    }
    $xpath = new DOMXPath($xml);
    if ($xpath->query('//failure|//error')->length !== 0) {
        throw new RuntimeException('Regression failures remain.');
    }
    $required = ['test_real_worker_failed_job_retry_and_crash_recovery',
        'test_real_scheduler_expires_once_and_release_route_cache_rehearsal',
        'test_full_backup_restores_all_tables_foreign_keys_and_inventory_balances',
        'test_sandbox_row_lock_wait_is_measured_and_no_balance_changes', 'test_approved_budgets_in_fresh_subprocesses',
        'test_database_cache_does_not_add_queries_per_attribute_accessor',
        'test_ci_is_fail_closed_and_enables_all_critical_sandbox_suites', 'test_framework_channel_applies_redaction_before_writing'];
    foreach ($required as $name) {
        $nodes = $xpath->query('//testcase[@name="'.$name.'"]');
        if ($nodes->length !== 1 || $nodes->item(0)->getElementsByTagName('skipped')->length !== 0) {
            throw new RuntimeException('Required P7 rehearsal was skipped or missing.');
        }
    }
    $receipt = json_decode(file_get_contents($root.'/tests/.runtime/p6-rollout.json'), true, flags: JSON_THROW_ON_ERROR);
    $sandbox = new Tests\Support\DatabaseSandbox;
    $source = $sandbox->source();
    $preserved = [];
    foreach ($receipt['business_before'] as $table => $expected) {
        foreach ([$table, ...$expected['columns']] as $identifier) {
            if (! preg_match('/^[a-z_]+$/D', $identifier)) {
                throw new RuntimeException('Invalid receipt identifier.');
            }
        }
        $rows = $source->query('SELECT `'.implode('`, `', $expected['columns']).'` FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $equal = count($rows) === $expected['count'] && hash_equals($expected['sha256'], hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)));
        if (! $equal) {
            throw new RuntimeException('Source differs from P6 reference; review external writes before making preservation claims.');
        }
        $preserved[$table] = ['count' => count($rows), 'p6_original_columns_preserved' => true];
    }
    $settings = Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
    $before = json_decode(file_get_contents($root.'/docs/architecture/p7-performance-before.json'), true, flags: JSON_THROW_ON_ERROR);
    $after = json_decode(file_get_contents($root.'/docs/architecture/p7-performance-after.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($before['dataset'] !== $after['dataset']) {
        throw new RuntimeException('Benchmark datasets differ.');
    }
    foreach (['detail', 'home', 'csv'] as $workload) {
        if ($before['results'][$workload]['response_sha256'] !== $after['results'][$workload]['response_sha256']) {
            throw new RuntimeException('Benchmark response changed.');
        }
    }
    if ($after['results']['detail']['query_count_max'] > 8 || $after['results']['home']['query_count_max'] > 5
        || $after['results']['csv']['peak_memory_bytes'] > 64 * 1024 * 1024) {
        throw new RuntimeException('Approved budget exceeded.');
    }
    $report = ['phase' => 'P7', 'verified_at_utc' => gmdate(DATE_ATOM),
        'junit' => ['tests' => $xpath->query('//testcase')->length, 'assertions' => (int) $xml->getElementsByTagName('testsuite')->item(0)->getAttribute('assertions'),
            'skipped' => $xpath->query('//skipped')->length, 'failures' => 0, 'errors' => 0],
        'sandbox_rehearsals_passed' => $required, 'benchmark_dataset_and_body_hashes_equal' => true, 'approved_budgets_passed' => true,
        'source_migration_count' => (int) $source->query('SELECT COUNT(*) FROM migrations')->fetchColumn(),
        'source_reference' => 'Private P6 rollout receipt; original-column projections, not a claim about later additive ledger columns.',
        'source_business_reference_comparisons' => $preserved,
        'runtime_gates_from_env' => ['inventory_protocol_enabled' => filter_var($settings['INVENTORY_PROTOCOL_ENABLED'] ?? false, FILTER_VALIDATE_BOOL),
            'order_transitions_enabled' => filter_var($settings['ORDER_TRANSITIONS_ENABLED'] ?? false, FILTER_VALIDATE_BOOL),
            'vnpay_enabled' => filter_var($settings['VNPAY_ENABLED'] ?? false, FILTER_VALIDATE_BOOL)],
        'github_actions_run' => 'Not executed remotely; workflow syntax/policy tested locally. No push/deploy performed.'];
    file_put_contents($root.'/docs/architecture/p7-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    echo "P7 verification recorded; source original-column fingerprints preserved against P6 receipt.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'P7 verification stopped: '.$error::class.'; '.$error->getMessage()."\n");
    exit(1);
}
