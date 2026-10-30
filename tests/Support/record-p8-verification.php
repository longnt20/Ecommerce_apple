<?php

require __DIR__.'/../../vendor/autoload.php';

$root = dirname(__DIR__, 2);
try {
    $xml = new DOMDocument;
    if (! $xml->load($root.'/tests/.runtime/p8-full-junit.xml', LIBXML_NONET)) {
        throw new RuntimeException('Full P8 JUnit required.');
    }
    $xpath = new DOMXPath($xml);
    if ($xpath->query('//failure|//error')->length !== 0) {
        throw new RuntimeException('Regression failures remain.');
    }
    $required = ['test_real_producer_crash_after_business_commit_before_publish_loses_no_event',
        'test_business_and_outbox_rollback_together_and_independent_relay_can_deliver',
        'test_failed_outbox_insert_rolls_back_actual_product_and_variants',
        'test_relay_crash_after_publish_retries_original_event_and_replay_is_audited',
        'test_consumer_crash_before_and_after_effect_is_atomic_and_post_commit_retry_deduplicates',
        'test_reorder_unsupported_schema_identity_conflict_and_offline_replay',
        'test_schema_is_repeatable_consumer_has_no_source_access_and_disabled_mode_still_writes',
        'test_concurrent_consumers_apply_one_effect_and_relay_failure_keeps_pending_event',
        'test_concurrent_product_writers_allocate_distinct_ordered_stream_versions',
        'test_normal_monolith_needs_no_experimental_schema_and_keeps_stock_unchanged',
        'test_malformed_transport_is_quarantined_and_does_not_block_valid_events'];
    foreach ($required as $name) {
        $nodes = $xpath->query('//testcase[@name="'.$name.'"]');
        if ($nodes->length !== 1 || $nodes->item(0)->getElementsByTagName('skipped')->length !== 0) {
            throw new RuntimeException('Required P8 test missing or skipped.');
        }
    }
    $performance = json_decode(file_get_contents($root.'/docs/architecture/p8-performance.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($performance['reads']['monolith']['response_sha256'] !== $performance['reads']['projection']['response_sha256']
        || $performance['offline_and_catchup']['inbox_count'] !== 1030 || $performance['offline_and_catchup']['remaining_unpublished'] !== 0
        || $performance['offline_and_catchup']['outcomes']['quarantined'] !== 0) {
        throw new RuntimeException('P8 benchmark equivalence/catch-up failed.');
    }
    $admin = new Tests\Support\DatabaseSandbox;
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
            throw new RuntimeException('Source differs from P6 receipt; preservation claim withheld.');
        }
        $preserved[$table] = ['count' => count($rows), 'original_columns_preserved' => true];
    }
    $experimental = (int) $source->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND LEFT(TABLE_NAME,3)='p8_'")->fetchColumn();
    if ($experimental !== 0 || (int) $source->query('SELECT COUNT(*) FROM migrations')->fetchColumn() !== 56) {
        throw new RuntimeException('Unexpected source experimental schema or migration count.');
    }
    $settings = Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
    if (filter_var($settings['P8_REPORTING_EXPERIMENT'] ?? false, FILTER_VALIDATE_BOOL)) {
        throw new RuntimeException('Source experiment must remain disabled.');
    }
    $report = ['phase' => 'P8', 'verified_at_utc' => gmdate(DATE_ATOM), 'scope' => 'Isolated local experiment, no production deployment',
        'junit' => ['tests' => $xpath->query('//testcase')->length, 'assertions' => (int) $xml->getElementsByTagName('testsuite')->item(0)->getAttribute('assertions'),
            'skipped' => $xpath->query('//skipped')->length, 'failures' => 0, 'errors' => 0],
        'required_recovery_and_isolation_tests_passed' => $required,
        'source_experiment_enabled' => false, 'source_experimental_tables' => 0, 'source_migration_count' => 56,
        'source_reference' => 'Private P6 rollout receipt; original-column projections of 20 business tables only.',
        'source_business_comparisons' => $preserved, 'equivalent_projection_and_offline_catchup' => true,
        'transport' => 'Local private file spool; process crash/replay tested, network broker/physical host failure not tested',
        'external_email_or_payment_effects' => 'None in experiment; Reporting DB projection only',
        'github_actions_run' => 'Not executed remotely',
        'decision' => 'Keep Modular Monolith default; retain experiment, no independent production service yet'];
    file_put_contents($root.'/docs/architecture/p8-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    echo "P8 verification recorded; source P6 fingerprints preserved, no source P8 schema.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'P8 verification stopped: '.$error::class.'; '.$error->getMessage()."\n");
    exit(1);
}
