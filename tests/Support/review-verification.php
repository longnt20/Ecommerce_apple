<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Tests\Support\DatabaseSandbox;

$root = dirname(__DIR__, 2);
try {
    $xml = new DOMDocument;
    if (! $xml->load($root.'/tests/.runtime/review-junit.xml', LIBXML_NONET)) {
        throw new RuntimeException('Review JUnit required.');
    }
    $xpath = new DOMXPath($xml);
    if ($xpath->query('//failure|//error|//incomplete')->length !== 0) {
        throw new RuntimeException('Review regressions remain.');
    }
    $required = ['test_native_migrate_bootstraps_an_empty_database_and_new_writers_work',
        'test_two_independent_processes_can_only_record_payment_once',
        'test_two_buyers_of_last_unit_create_exactly_one_order_and_reservation',
        'test_concurrent_collection_replay_and_partial_refund_cap_are_serialized',
        'test_concurrent_returns_cannot_receive_the_same_shipped_unit_twice',
        'test_real_worker_failed_job_retry_and_crash_recovery',
        'test_real_scheduler_expires_once_and_release_route_cache_rehearsal',
        'test_full_backup_restores_all_tables_foreign_keys_and_inventory_balances',
        'test_real_producer_crash_after_business_commit_before_publish_loses_no_event',
        'test_consumer_crash_before_and_after_effect_is_atomic_and_post_commit_retry_deduplicates',
        'test_serialization_failure_removes_temporary_file_without_publishing'];
    foreach ($required as $name) {
        $nodes = $xpath->query('//testcase[@name="'.$name.'"]');
        if ($nodes->length !== 1 || $nodes->item(0)->getElementsByTagName('skipped')->length !== 0) {
            throw new RuntimeException('Required review test missing or skipped.');
        }
    }
    $admin = new DatabaseSandbox;
    $source = $admin->source();
    $receipt = json_decode(file_get_contents($root.'/tests/.runtime/p6-rollout.json'), true, flags: JSON_THROW_ON_ERROR);
    $preserved = [];
    foreach ($receipt['business_before'] as $table => $expected) {
        foreach ([$table, ...$expected['columns']] as $identifier) {
            if (! preg_match('/^[a-z_]+$/D', $identifier)) {
                throw new RuntimeException('Invalid receipt identifier.');
            }
        }
        $rows = $source->query('SELECT `'.implode('`, `', $expected['columns']).'` FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) !== $expected['count'] || ! hash_equals($expected['sha256'], hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)))) {
            throw new RuntimeException('Source differs from private P6 receipt; preservation claim withheld.');
        }
        $preserved[$table] = ['rows' => count($rows), 'original_columns_preserved' => true];
    }
    $demo = json_decode(file_get_contents($root.'/docs/portfolio/demo-verification.json'), true, flags: JSON_THROW_ON_ERROR);
    $report = ['reviewed_at_utc' => gmdate(DATE_ATOM),
        'junit' => ['tests' => $xpath->query('//testcase')->length, 'skipped' => $xpath->query('//skipped')->length,
            'assertions' => (int) $xml->getElementsByTagName('testsuite')->item(0)->getAttribute('assertions'), 'failures' => 0, 'errors' => 0],
        'source_migrations' => (int) $source->query('SELECT COUNT(*) FROM migrations')->fetchColumn(),
        'source_p8_tables' => (int) $source->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND LEFT(TABLE_NAME,3)='p8_'")->fetchColumn(),
        'source_comparison_scope' => 'Original-column projections against private P6 receipt, not every new column or table.',
        'required_tests_executed' => $required,
        'preserved' => $preserved, 'demo_checks' => count($demo['checks']), 'demo_runtime_cleaned' => $demo['runtime_cleaned']];
    file_put_contents($root.'/docs/review/local-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    echo "Review verification recorded; original-column source fingerprints match.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Review verification failed: '.$error::class."; details withheld.\n");
    exit(1);
}
