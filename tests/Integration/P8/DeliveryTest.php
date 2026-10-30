<?php

namespace Tests\Integration\P8;

use App\Experiments\P8\ProjectionConsumer;
use App\Modules\Catalog\Infrastructure\SaveProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\P8Sandbox;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    private ?P8Sandbox $sandbox = null;

    protected function setUp(): void
    {
        if (getenv('P8_SCHEMA_TESTS') !== '1') {
            $this->markTestSkipped('P8_SCHEMA_TESTS=1 permits two disposable schemas and restricted experimental accounts.');
        }
        parent::setUp();
        $this->sandbox = new P8Sandbox;
    }

    protected function tearDown(): void
    {
        if ($this->sandbox) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->sandbox->close();
        }
        parent::tearDown();
    }

    private function save(string $name = 'P8 updated'): void
    {
        app(SaveProduct::class)->save(['name' => $name, 'category_id' => 910001, 'status' => 'published', 'visibility' => 'public',
            'variants' => [['id' => 2000001, 'sku' => 'P7-SKU-2000001', 'price' => 123456, 'cost_price' => 60000]]], 1000001);
    }

    private function event(): array
    {
        return json_decode(DB::table('p8_outbox_messages')->orderByDesc('aggregate_version')->value('envelope'), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_business_and_outbox_rollback_together_and_independent_relay_can_deliver(): void
    {
        DB::beginTransaction();
        $this->save('Uncommitted');
        $this->assertSame(1, DB::table('p8_outbox_messages')->count());
        $this->assertSame(['published' => 0, 'failed' => 0], $this->sandbox->run('relay'));
        DB::rollBack();
        $this->assertSame(0, DB::table('p8_outbox_messages')->count());
        $this->assertSame('P7 phone 1', DB::table('products')->value('name'));
        $this->save();
        $this->assertSame(1, DB::table('p8_outbox_messages')->whereNull('published_at')->count());
        $this->assertSame(0, (int) $this->sandbox->consumer->query('SELECT COUNT(*) FROM p8_inbox')->fetchColumn());
        $this->assertSame(1, $this->sandbox->run('relay')['published']);
        $this->assertSame(1, $this->sandbox->run('consume')['applied']);
        $this->assertSame('P8 updated', $this->sandbox->consumer->query('SELECT name FROM p8_catalog_cards')->fetchColumn());
    }

    public function test_failed_outbox_insert_rolls_back_actual_product_and_variants(): void
    {
        DB::unprepared("CREATE TRIGGER p8_reject_event BEFORE INSERT ON p8_outbox_messages FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic failure'");
        try {
            $this->save();
            $this->fail('The business write must fail atomically with its outbox.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertSame('P7 phone 1', DB::table('products')->value('name'));
            $this->assertSame('25000000.00', DB::table('product_variants')->value('price'));
            $this->assertSame(0, DB::table('p8_outbox_streams')->count());
        } finally {
            DB::unprepared('DROP TRIGGER p8_reject_event');
        }
    }

    public function test_relay_crash_after_publish_retries_original_event_and_replay_is_audited(): void
    {
        $this->save();
        $event = $this->event();
        $this->killAt($this->sandbox->worker('relay', ['fault' => 'after-publish']));
        $this->assertFileExists($this->sandbox->spool.'/'.$event['id'].'.json');
        $this->assertSame(1, DB::table('p8_outbox_messages')->whereNull('published_at')->count());
        $this->assertSame(1, $this->sandbox->run('relay')['published']);
        $this->assertSame(1, $this->sandbox->run('consume')['applied']);
        $this->assertSame(1, $this->sandbox->run('relay', ['replay_id' => $event['id']])['published']);
        $this->assertSame(1, $this->sandbox->run('consume')['duplicate']);
        $this->assertSame(1, (int) $this->sandbox->consumer->query('SELECT apply_count FROM p8_catalog_cards')->fetchColumn());
        $this->assertSame(1, DB::table('p8_publish_attempts')->where('outcome', 'replay_requested')->count());
        $this->assertSame(1, DB::table('p8_outbox_messages')->count());
    }

    public function test_consumer_crash_before_and_after_effect_is_atomic_and_post_commit_retry_deduplicates(): void
    {
        $this->save();
        $this->sandbox->run('relay');
        foreach (['before-effect', 'after-effect-before-commit'] as $stage) {
            $this->killAt($this->sandbox->worker('consume', ['fault' => $stage]));
            $this->assertSame(0, (int) $this->sandbox->consumer->query('SELECT COUNT(*) FROM p8_inbox')->fetchColumn());
            $this->assertSame(0, (int) $this->sandbox->consumer->query('SELECT COUNT(*) FROM p8_catalog_cards')->fetchColumn());
        }
        $this->killAt($this->sandbox->worker('consume', ['fault' => 'after-commit']));
        $this->assertSame(1, $this->sandbox->run('consume')['duplicate']);
        $this->assertSame(1, (int) $this->sandbox->consumer->query('SELECT apply_count FROM p8_catalog_cards')->fetchColumn());
    }

    public function test_reorder_unsupported_schema_identity_conflict_and_offline_replay(): void
    {
        $this->save('First');
        $first = $this->event();
        $this->save('Second');
        $second = $this->event();
        $this->assertSame(2, DB::table('p8_outbox_messages')->whereNull('published_at')->count());
        $this->sandbox->run('relay'); // Consumer was offline while writes and delivery continued.
        $consumer = new ProjectionConsumer($this->sandbox->consumer);
        $this->assertSame('applied', $consumer->consume($second));
        $this->assertSame('stale', $consumer->consume($first));
        $this->assertSame('duplicate', $consumer->consume($first));
        $future = array_replace($second, ['id' => (string) Str::uuid(), 'schema_version' => 2]);
        $this->assertSame('quarantined', $consumer->consume($future));
        $conflict = $second;
        $conflict['payload']['name'] = 'Conflicting event identity';
        $this->assertSame('quarantined', $consumer->consume($conflict));
        $conflictingVersion = array_replace($second, ['id' => (string) Str::uuid()]);
        $this->assertSame('quarantined', $consumer->consume($conflictingVersion));
        $this->assertSame('Second', $this->sandbox->consumer->query('SELECT name FROM p8_catalog_cards')->fetchColumn());
        $this->assertSame(2, (int) $this->sandbox->consumer->query('SELECT COUNT(*) FROM p8_inbox')->fetchColumn());
        $this->assertSame(3, (int) $this->sandbox->consumer->query('SELECT COUNT(*) FROM p8_quarantine')->fetchColumn());
        $this->assertSame(2, $this->sandbox->run('consume')['duplicate']);
    }

    public function test_schema_is_repeatable_consumer_has_no_source_access_and_disabled_mode_still_writes(): void
    {
        \App\Experiments\P8\Schema::apply($this->sandbox->producer->admin->connectSandbox($this->sandbox->consumerConnection['database']), 'consumer');
        try {
            $this->sandbox->consumer->query('SELECT COUNT(*) FROM `'.$this->sandbox->producer->connection['database'].'`.products');
            $this->fail('Consumer must not access producer business tables.');
        } catch (\PDOException $error) {
            $this->assertContains((int) $error->errorInfo[1], [1044, 1142]);
        }
        config(['p8.enabled' => false]);
        $this->save('Monolith independent');
        $this->assertSame('Monolith independent', DB::table('products')->value('name'));
        $this->assertSame(0, DB::table('p8_outbox_messages')->count());
    }

    public function test_concurrent_consumers_apply_one_effect_and_relay_failure_keeps_pending_event(): void
    {
        $this->save();
        $event = $this->event();
        $file = $this->sandbox->spool.'/'.$event['id'].'.json';
        file_put_contents($file, json_encode(array_replace($event, ['schema_version' => 2])));
        $this->assertSame(1, $this->sandbox->run('relay')['failed']);
        $this->assertSame(1, DB::table('p8_outbox_messages')->whereNull('published_at')->count());
        unlink($file); // Only this synthetic poison transport file; the outbox is retained.
        $this->sandbox->run('relay');
        $first = $this->sandbox->worker('consume');
        $second = $this->sandbox->worker('consume');
        $first->start();
        $second->start();
        $this->assertSame(0, $first->wait());
        $this->assertSame(0, $second->wait());
        $this->assertSame(1, (int) $this->sandbox->consumer->query('SELECT apply_count FROM p8_catalog_cards')->fetchColumn());
        $this->assertSame(1, (int) $this->sandbox->consumer->query('SELECT COUNT(*) FROM p8_inbox')->fetchColumn());
    }

    private function killAt(\Symfony\Component\Process\Process $worker): void
    {
        try {
            $worker->start();
            $this->assertTrue($worker->waitUntil(fn ($type, $output) => str_contains($output, 'P8_FAULT_READY')),
                'Worker must reach the durable crash barrier.');
        } finally {
            $worker->stop(0);
        }
    }

    public function test_real_producer_crash_after_business_commit_before_publish_loses_no_event(): void
    {
        $worker = new \Symfony\Component\Process\Process([PHP_BINARY, base_path('tests/Support/p8-producer-worker.php')], base_path());
        $worker->setInput(json_encode(['connection' => $this->sandbox->producer->connection]))->setTimeout(60);
        $this->killAt($worker);
        $this->assertSame('Committed before producer died', DB::table('products')->value('name'));
        $this->assertSame(1, DB::table('p8_outbox_messages')->whereNull('published_at')->count());
        $this->assertSame(1, $this->sandbox->run('relay')['published']);
        $this->assertSame(1, $this->sandbox->run('consume')['applied']);
    }

    public function test_standalone_variant_write_records_final_aggregate_snapshot_in_same_transaction(): void
    {
        app(SaveProduct::class)->variant(['price' => 54321], 2000001);
        $event = $this->event();
        $this->assertSame('54321.00', $event['payload']['min_price']);
        $this->assertSame(1, $event['aggregate_version']);
        $this->sandbox->run('relay');
        $this->assertSame(1, $this->sandbox->run('consume')['applied']);
        $this->assertSame('54321.00', $this->sandbox->consumer->query('SELECT min_price FROM p8_catalog_cards')->fetchColumn());
    }

    public function test_malformed_transport_is_quarantined_and_does_not_block_valid_events(): void
    {
        $this->save();
        $this->sandbox->run('relay');
        file_put_contents($this->sandbox->spool.'/00000000-0000-4000-8000-000000000000.json', '{broken');
        $result = $this->sandbox->run('consume');
        $this->assertSame(1, $result['quarantined']);
        $this->assertSame(1, $result['applied']);
        $this->assertSame('invalid_json', $this->sandbox->consumer->query('SELECT reason_code FROM p8_quarantine')->fetchColumn());
    }

    public function test_concurrent_product_writers_allocate_distinct_ordered_stream_versions(): void
    {
        $workers = [];
        DB::beginTransaction();
        DB::table('products')->where('id', 1000001)->lockForUpdate()->firstOrFail();
        try {
            foreach (['Concurrent A', 'Concurrent B'] as $name) {
                $worker = new \Symfony\Component\Process\Process([PHP_BINARY, base_path('tests/Support/p8-producer-worker.php')], base_path());
                $worker->setInput(json_encode(['connection' => $this->sandbox->producer->connection, 'mode' => 'once', 'name' => $name]))->setTimeout(30);
                $workers[] = $worker;
                $worker->start();
                $this->assertTrue($worker->waitUntil(fn ($type, $output) => str_contains($output, 'P8_PRODUCER_READY')));
            }
            DB::rollBack();
            foreach ($workers as $worker) {
                $this->assertSame(0, $worker->wait());
            }
            $this->assertSame([1, 2], DB::table('p8_outbox_messages')->orderBy('aggregate_version')->pluck('aggregate_version')->all());
            $this->assertSame(DB::table('products')->value('name'), $this->event()['payload']['name']);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($workers as $worker) {
                $worker->stop(0);
            }
        }
    }
}
