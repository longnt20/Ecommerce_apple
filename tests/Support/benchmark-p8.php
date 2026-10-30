<?php

require __DIR__.'/../../vendor/autoload.php';

use App\Experiments\P8\CatalogOutbox;
use App\Experiments\P8\MonolithCards;
use App\Experiments\P8\ProjectionCards;
use App\Modules\Catalog\Infrastructure\SaveProduct;
use Illuminate\Support\Facades\DB;
use Tests\Support\P8Sandbox;

$sandbox = null;
try {
    putenv('P0_DB_MODE=disabled');
    Tests\Support\TestApplication::create();
    $sandbox = new P8Sandbox(1000, 3);
    $percentiles = static function (array $samples): array {
        sort($samples);

        return ['samples' => count($samples), 'median_ms' => round($samples[(int) floor(count($samples) / 2)], 3),
            'p95_ms' => round($samples[(int) ceil(count($samples) * .95) - 1], 3)];
    };
    $writes = [];
    foreach (['monolith' => false, 'with_outbox' => true] as $mode => $enabled) {
        config(['p8.enabled' => $enabled]);
        $times = $counts = $statements = [];
        for ($i = 0; $i < 30; $i++) {
            app()->forgetScopedInstances();
            DB::enableQueryLog();
            DB::flushQueryLog();
            $before = (int) DB::connection()->getPdo()->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch(PDO::FETCH_ASSOC)['Value'];
            $start = hrtime(true);
            app(SaveProduct::class)->save(['name' => 'P8 benchmark '.$i, 'category_id' => 910001, 'status' => 'published', 'visibility' => 'public'], 1000001);
            $times[] = (hrtime(true) - $start) / 1e6;
            $after = (int) DB::connection()->getPdo()->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch(PDO::FETCH_ASSOC)['Value'];
            $statements[] = $after - $before - 1;
            $counts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
        }
        $writes[$mode] = $percentiles($times) + ['orm_query_count_max' => max($counts), 'mysql_statement_count_max' => max($statements)];
    }
    $pendingDuringOffline = DB::table('p8_outbox_messages')->whereNull('published_at')->count();
    // Explicit synthetic bootstrap: lock each aggregate and publish a full snapshot; no source backfill.
    for ($i = 1; $i <= 1000; $i++) {
        DB::transaction(fn () => app(CatalogOutbox::class)->record(1000000 + $i));
    }
    $start = hrtime(true);
    $published = 0;
    do {
        $result = $sandbox->run('relay');
        $published += $result['published'];
        if ($result['failed'] !== 0) {
            throw new RuntimeException('Transport failed.');
        }
    } while ($result['published'] > 0);
    $consumed = ['applied' => 0, 'stale' => 0, 'duplicate' => 0, 'quarantined' => 0];
    for ($offset = 0; $offset < $published; $offset += 1000) {
        foreach ($sandbox->run('consume', ['offset' => $offset]) as $key => $value) {
            $consumed[$key] += $value;
        }
    }
    $catchupMs = (hrtime(true) - $start) / 1e6;
    $readers = ['monolith' => new MonolithCards, 'projection' => new ProjectionCards($sandbox->consumer)];
    $reads = $hashes = [];
    foreach ($readers as $name => $reader) {
        $times = $queries = [];
        for ($i = 0; $i < 30; $i++) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $before = (int) $sandbox->consumer->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch()['Value'];
            $start = hrtime(true);
            $cards = $reader->page();
            $hashes[$name] = hash('sha256', json_encode($cards, JSON_THROW_ON_ERROR));
            $times[] = (hrtime(true) - $start) / 1e6;
            $after = (int) $sandbox->consumer->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch()['Value'];
            $queries[] = $name === 'monolith' ? count(DB::getQueryLog()) : $after - $before - 1;
            DB::disableQueryLog();
        }
        $reads[$name] = $percentiles($times) + ['query_count_max' => max($queries), 'response_sha256' => $hashes[$name]];
    }
    if ($hashes['monolith'] !== $hashes['projection'] || $consumed['quarantined'] !== 0
        || $pendingDuringOffline !== 30 || $published !== 1030
        || (int) $sandbox->consumer->query('SELECT COUNT(*) FROM p8_catalog_cards')->fetchColumn() !== 1000) {
        throw new RuntimeException('Projection catch-up or read equivalence failed.');
    }
    $event = DB::table('p8_outbox_messages')->orderBy('created_at')->first();
    $replay = $sandbox->run('relay', ['replay_id' => $event->id]);
    $duplicateReplay = $sandbox->run('consume');
    $report = ['phase' => 'P8', 'recorded_at_utc' => gmdate(DATE_ATOM), 'php' => PHP_VERSION,
        'mysql' => DB::selectOne('SELECT VERSION() AS version')->version,
        'dataset' => ['products' => 1000, 'skus_per_product' => 3, 'warehouses' => 5, 'inventory_rows' => 15000, 'card_page_size' => 20],
        'method' => 'Sequential local diagnostic, 30 samples each; same catalog card fields and fixture, source eager loading vs independent PDO projection. Includes SQL/mapping, excludes fixture/bootstrap and role verification. Write benchmark excludes media/uploads. Not public home endpoint or production SLO.',
        'writes' => $writes, 'reads' => $reads,
        'offline_and_catchup' => ['committed_events_while_consumer_offline' => $pendingDuringOffline, 'total_published' => $published,
            'outcomes' => $consumed, 'relay_and_consumer_subprocess_wall_ms' => round($catchupMs, 3),
            'inbox_count' => (int) $sandbox->consumer->query('SELECT COUNT(*) FROM p8_inbox')->fetchColumn(),
            'projection_rows' => 1000, 'remaining_unpublished' => DB::table('p8_outbox_messages')->whereNull('published_at')->count()],
        'replay' => ['original_event_republished' => $replay['published'], 'duplicate_page_count' => $duplicateReplay['duplicate']],
        'retained_transport' => ['outbox_messages' => DB::table('p8_outbox_messages')->count(),
            'outbox_envelope_bytes' => (int) DB::table('p8_outbox_messages')->selectRaw('SUM(OCTET_LENGTH(envelope)) AS bytes')->value('bytes'),
            'spool_files' => count(glob($sandbox->spool.'/*.json')), 'spool_bytes' => array_sum(array_map('filesize', glob($sandbox->spool.'/*.json')))],
        'decision' => 'Keep Modular Monolith as production default; retain isolated Reporting experiment for learning. A narrow projection query reduction does not justify independent production service without concurrent load, network transport and operational ownership.'];
    file_put_contents(dirname(__DIR__, 2).'/docs/architecture/p8-performance.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    echo "P8 independent projection benchmark recorded; source application untouched.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'P8 benchmark failed: '.$error::class.'; '.$error->getMessage()."\n");
    exit(1);
} finally {
    $sandbox?->close();
}
