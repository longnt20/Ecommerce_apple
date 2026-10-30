<?php

namespace Tests\Integration\P6;

use App\Exceptions\InventoryConflict;
use App\Models\User;
use App\Modules\Inventory\Infrastructure\StockWriter;
use App\Modules\Ordering\Contracts\Checkout;
use App\Services\InventoryService;
use App\SharedKernel\ActorContext;
use Illuminate\Support\Facades\DB;
use Tests\Support\MySqlTestCase;

class InventoryDocumentsTest extends MySqlTestCase
{
    public function test_transfer_document_and_lines_commit_together_and_replay_once(): void
    {
        $this->seedCommerce();
        DB::table('users')->where('id', 910002)->update(['role' => 'admin']);
        $service = app(InventoryService::class);
        $work = fn () => [$service->transfer(1, 2, 910001, 2, 'Physical transfer accepted', 910002, 'transfer-line-1')];
        $payload = ['warehouse_id' => 1, 'to_warehouse_id' => 2, 'quantity' => 2];
        $first = $service->batch('transfer', 910002, 'transfer-document', $payload, $work);
        $this->assertSame($first, $service->batch('transfer', 910002, 'transfer-document', $payload, $work));
        $this->assertDatabaseCount('inventory_documents', 1);
        $this->assertDatabaseCount('inventory_document_lines', 1);
        $this->assertDatabaseCount('inventory_transactions', 2);
        $this->assertDatabaseHas('inventory', ['warehouse_id' => 1, 'quantity' => 3]);
        $this->assertDatabaseHas('inventory', ['warehouse_id' => 2, 'quantity' => 7]);
        $this->actingAs(User::findOrFail(910002))->getJson('/admin/inventory/documents/'.$first['document_id'])->assertOk()->assertJsonCount(1, 'lines');
    }

    public function test_discrepancy_requires_fresh_count_above_reserved_and_records_audited_resolution(): void
    {
        $this->seedCommerce();
        DB::table('users')->where('id', 910002)->update(['role' => 'admin']);
        app(Checkout::class)->place($this->checkoutPayload(), new ActorContext(910001));
        $result = app(InventoryService::class)->adjust(1, 910001, 0, 'Count below held', 910002, 'stocktake');
        try {
            app(StockWriter::class)->resolve($result['discrepancy_id'], 0, 'Still insufficient', 910002, 'resolve-bad');
            $this->fail('Unfulfilled reservations may not disappear.');
        } catch (InventoryConflict $error) {
            $this->assertSame('discrepancy_unresolved', $error->reasonCode);
        }
        $resolved = app(StockWriter::class)->resolve($result['discrepancy_id'], 3, 'Recount accepted', 910002, 'resolve-good');
        $this->assertEquals($resolved, app(StockWriter::class)->resolve($result['discrepancy_id'], 3, 'Recount accepted', 910002, 'resolve-good'));
        $this->assertDatabaseHas('inventory_discrepancies', ['id' => $result['discrepancy_id'], 'status' => 'resolved', 'counted_quantity' => 0]);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 3, 'reserved_quantity' => 1, 'available_quantity' => 2]);
        $this->assertDatabaseHas('inventory_transactions', ['reference_type' => 'inventory_discrepancy', 'reference_id' => $result['discrepancy_id'], 'created_by' => 910002]);
    }

    public function test_settings_persist_but_cannot_disable_reservations_or_allow_negative_stock(): void
    {
        $this->seedCommerce();
        DB::table('users')->where('id', 910002)->update(['role' => 'admin']);
        $this->actingAs(User::findOrFail(910002));
        $this->post('/admin/inventory/settings', ['low_stock_threshold' => 17, 'auto_reserve' => 1, 'negative_stock_allowed' => 0])->assertRedirect();
        $this->get('/admin/inventory/settings')->assertOk()->assertSee('value="17"', false);
        $this->postJson('/admin/inventory/settings', ['low_stock_threshold' => 2, 'auto_reserve' => 0, 'negative_stock_allowed' => 1])->assertUnprocessable();
        $this->assertDatabaseHas('inventory_settings', ['id' => 1, 'low_stock_threshold' => 17, 'actor_id' => 910002]);
    }
}
