<?php

namespace Tests\Integration\P7;

use App\Http\Controllers\Api\HomeController;
use App\Models\Product;
use App\Reporting\InventoryReadQuery;
use App\Services\CatalogReadCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\Support\P7Sandbox;
use Tests\TestCase;

class ReadPerformanceTest extends TestCase
{
    private ?P7Sandbox $sandbox = null;

    protected function setUp(): void
    {
        if (getenv('P7_SCHEMA_TESTS') !== '1') {
            $this->markTestSkipped('P7_SCHEMA_TESTS=1 permits disposable synthetic MySQL schemas.');
        }
        parent::setUp();
        $this->sandbox = new P7Sandbox;
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

    public function test_search_cannot_escape_warehouse_or_status_and_export_uses_same_filter(): void
    {
        $this->sandbox->catalog(2, 1, 2);
        DB::table('inventory')->where('id', 3000001)->update(['quantity' => 0, 'available_quantity' => 0]);
        $request = Request::create('/', 'GET', ['warehouse_id' => 1, 'status' => 'out_of_stock', 'search' => 'P7 phone']);
        $read = app(InventoryReadQuery::class);
        $this->assertSame([3000001], $read->filtered($request)->pluck('id')->all());
        $this->assertSame([3000001], $read->rows($request)->pluck('id')->all());
        foreach (['=HYPERLINK("bad")', '+123', '-123', '@SUM(1)', "\tformula"] as $text) {
            $this->assertSame("'".$text, InventoryReadQuery::csvText($text));
        }
        $this->assertSame('Ordinary phone', InventoryReadQuery::csvText('Ordinary phone'));
    }

    public function test_detail_has_bounded_queries_and_includes_unavailable_reserved_stock(): void
    {
        $this->sandbox->catalog(2, 12, 5);
        DB::table('inventory')->where('id', 3000001)->update(['available_quantity' => 0, 'reserved_quantity' => 10]);
        DB::enableQueryLog();
        Cache::flush();
        $response = app(HomeController::class)->productDetail(Request::create('/'), 'p7-phone-1');
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertLessThanOrEqual(8, $queries);
        $data = $response->getData(true)['data'];
        $this->assertCount(12, $data['variants']);
        $this->assertSame(50, $data['variants'][0]['stock_quantity']);
        $this->assertSame(40, $data['variants'][0]['available_quantity']);
        $this->assertSame(110, collect($data['warehouse'])->firstWhere('warehouse_id', 1)['stock']);
    }

    public function test_cache_commit_rollback_and_inflight_old_generation(): void
    {
        $this->sandbox->catalog(1, 1, 1);
        Cache::flush();
        $cache = app(CatalogReadCache::class);
        $read = fn () => Product::findOrFail(1000001)->name;
        $this->assertSame('P7 phone 1', $cache->remember('test', 60, $read));
        $generation = Cache::get('catalog_read_generation');
        DB::beginTransaction();
        Product::findOrFail(1000001)->update(['name' => 'Rolled back']);
        $this->assertSame($generation, Cache::get('catalog_read_generation'));
        $this->assertSame('Rolled back', $cache->remember('test', 60, $read));
        DB::rollBack();
        $this->assertSame($generation, Cache::get('catalog_read_generation'));
        $this->assertSame('P7 phone 1', $cache->remember('test', 60, $read));
        DB::transaction(fn () => Product::findOrFail(1000001)->update(['name' => 'Committed']));
        $this->assertSame('Committed', $cache->remember('test', 60, $read));
        $cache->remember('race', 60, function () use ($cache) {
            $cache->invalidate();

            return 'old build';
        });
        $this->assertSame('new build', $cache->remember('race', 60, fn () => 'new build'));
    }

    public function test_approved_budgets_in_fresh_subprocesses(): void
    {
        $this->sandbox->catalog();
        $run = function ($workload) {
            $process = new Process([PHP_BINARY, __DIR__.'/../../Support/p7-benchmark-worker.php'], base_path());
            $process->setInput(json_encode(['connection' => $this->sandbox->connection, 'workload' => $workload]))->setTimeout(180);
            $this->assertSame(0, $process->run(), $process->getErrorOutput());

            return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        };
        $this->assertLessThanOrEqual(8, $run('detail')['query_count_max']);
        $this->assertLessThanOrEqual(5, $run('home')['query_count_max']);
        $this->sandbox->close();
        $this->sandbox = new P7Sandbox;
        $this->sandbox->catalog(6667, 3, 5, 100000);
        $csv = $run('csv');
        $this->assertLessThanOrEqual(64 * 1024 * 1024, $csv['peak_memory_bytes']);
        $this->assertSame(102, $csv['query_count_max']); // High-water mark + 100 chunks + terminal query.
        $this->assertSame('7dd9d990a84e8cffc7bbc3685756c657d0e99f041d2ab3920df9798715a29368', $csv['response_sha256']);
    }

    public function test_database_cache_does_not_add_queries_per_attribute_accessor(): void
    {
        $this->sandbox->catalog(1, 1, 1);
        config(['cache.default' => 'database', 'cache.stores.database.connection' => 'p7']);
        Cache::forgetDriver('database');
        Cache::flush();
        app()->forgetScopedInstances();
        DB::enableQueryLog();
        try {
            $first = app(\App\Services\ProductAttributeService::class)->getColors();
            $initial = count(DB::getQueryLog());
            for ($i = 0; $i < 50; $i++) {
                $this->assertSame($first, app(\App\Services\ProductAttributeService::class)->getColors());
            }
            $this->assertSame($initial, count(DB::getQueryLog()));
            $this->assertLessThanOrEqual(5, $initial);
            // A new request must see another process's committed invalidation.
            DB::table('product_attributes')->where('type', 'color')->update(['label' => 'Committed elsewhere']);
            Cache::forever('catalog_read_generation', 'external-commit');
            app()->forgetScopedInstances();
            $this->assertSame('Committed elsewhere', app(\App\Services\ProductAttributeService::class)->getColors()['black']);
        } finally {
            DB::disableQueryLog();
        }
    }
}
