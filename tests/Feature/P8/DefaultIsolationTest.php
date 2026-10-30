<?php

namespace Tests\Feature\P8;

use App\Modules\Catalog\Contracts\CatalogProjectionEvents;
use App\Modules\Catalog\Infrastructure\NoCatalogProjectionEvents;
use App\Modules\Catalog\Infrastructure\SaveProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\MySqlTestCase;

class DefaultIsolationTest extends MySqlTestCase
{
    public function test_normal_monolith_needs_no_experimental_schema_and_keeps_stock_unchanged(): void
    {
        $this->seedCommerce();
        $this->assertFalse(config('p8.enabled'));
        $this->assertInstanceOf(NoCatalogProjectionEvents::class, app(CatalogProjectionEvents::class));
        $this->assertFalse(Schema::hasTable('p8_outbox_messages'));
        $before = DB::table('inventory')->orderBy('id')->get()->toJson();
        app(SaveProduct::class)->save(['name' => 'Still a monolith', 'category_id' => 910001], 910001);
        $this->assertDatabaseHas('products', ['id' => 910001, 'name' => 'Still a monolith']);
        $this->assertSame($before, DB::table('inventory')->orderBy('id')->get()->toJson());
        config(['p8.enabled' => true]);
        try {
            app(SaveProduct::class)->save(['name' => 'Must not commit', 'category_id' => 910001], 910001);
            $this->fail('Experimental publisher must reject the persistent P0 schema.');
        } catch (\RuntimeException $error) {
            $this->assertSame('p8_sandbox_required', $error->getMessage());
            $this->assertDatabaseHas('products', ['id' => 910001, 'name' => 'Still a monolith']);
        }
    }
}
