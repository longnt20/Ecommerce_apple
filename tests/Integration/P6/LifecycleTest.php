<?php

namespace Tests\Integration\P6;

use App\Exceptions\InventoryConflict;
use App\Models\InventoryTransaction;
use App\Models\User;
use App\Modules\Inventory\Contracts\InventoryMaintenance;
use App\Modules\Ordering\Contracts\AfterSales;
use App\Modules\Ordering\Contracts\Checkout;
use App\Modules\Ordering\Contracts\OrderTransitions;
use App\SharedKernel\ActorContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\MySqlTestCase;

class LifecycleTest extends MySqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCommerce();
        DB::table('users')->where('id', 910002)->update(['role' => 'admin']);
        config(['ordering.transitions_enabled' => true]);
    }

    private function shipped(int $quantity = 2): int
    {
        $input = $this->checkoutPayload();
        $input['items'][0]['quantity'] = $quantity;
        $id = app(Checkout::class)->place($input, new ActorContext(910001))->id;
        foreach (['confirmed', 'shipping', 'completed'] as $target) {
            app(OrderTransitions::class)->change($id, $target, new ActorContext(910002), $target.'-'.$id);
        }

        return $id;
    }

    private function money(int $id, string $key, array $input): array
    {
        return app(AfterSales::class)->money($id, new ActorContext(910002), $key, $input);
    }

    public function test_completed_cod_is_unpaid_until_exact_collection_and_retry_is_idempotent(): void
    {
        $id = $this->shipped();
        $this->assertSame(['completed', 'COD', 'unpaid', '50000000.00'], array_values((array) DB::table('orders')->where('id', $id)->first(['status', 'payment_method', 'payment_status', 'final_amount'])));
        $this->assertDatabaseHas('orders', ['id' => $id, 'status' => 'completed', 'payment_status' => 'unpaid']);
        $input = ['action' => 'collect_cod', 'amount' => 50000000, 'reference' => 'COD-P6-1'];
        $first = $this->money($id, 'collect-1', $input);
        $this->assertSame($first, $this->money($id, 'collect-1', $input));
        $this->assertDatabaseCount('payment_adjustments', 1);
        $this->assertDatabaseCount('payment_adjustment_events', 1);
        $this->assertDatabaseHas('orders', ['id' => $id, 'payment_status' => 'paid', 'transaction_id' => 'COD-P6-1']);
        $this->expectException(InventoryConflict::class);
        $this->money($id, 'collect-2', $input);
    }

    public function test_partial_refunds_reserve_cap_rejection_releases_it_and_full_settlement_never_changes_stock(): void
    {
        $id = $this->shipped();
        $this->money($id, 'collect', ['action' => 'collect_cod', 'amount' => 50000000, 'reference' => 'COD-P6-2']);
        $before = DB::table('inventory')->orderBy('id')->get()->toJson();
        $first = $this->money($id, 'refund-1', ['action' => 'request_refund', 'amount' => 20000000, 'reason' => 'Partial refund']);
        try {
            $this->money($id, 'over-refund', ['action' => 'request_refund', 'amount' => 40000000, 'reason' => 'Too much']);
            $this->fail('Requests must reserve the refundable amount.');
        } catch (InventoryConflict $error) {
            $this->assertSame('refund_exceeds_collected', $error->reasonCode);
        }
        $this->money($id, 'reject', ['action' => 'reject_refund', 'refund_id' => $first['id'], 'reason' => 'Reconciled as one full refund']);
        $full = $this->money($id, 'refund-full', ['action' => 'request_refund', 'amount' => 50000000, 'reason' => 'Full refund']);
        $settle = ['action' => 'settle_refund', 'refund_id' => $full['id'], 'reference' => 'BANK-P6-1', 'reason' => 'Bank statement verified'];
        $result = $this->money($id, 'settle', $settle);
        $this->assertSame($result, $this->money($id, 'settle', $settle));
        $this->assertSame('settled', $result['status']);
        $this->assertSame($before, DB::table('inventory')->orderBy('id')->get()->toJson());
        $this->assertDatabaseCount('inventory_return_receipts', 0);
        $this->assertDatabaseHas('orders', ['id' => $id, 'payment_status' => 'paid']);
    }

    public function test_damaged_and_sellable_returns_are_bounded_independent_of_refunds_and_replay_safe(): void
    {
        $id = $this->shipped();
        $item = DB::table('order_items')->where('order_id', $id)->value('id');
        $input = ['order_item_id' => $item, 'quantity' => 1, 'disposition' => 'damaged', 'reference' => 'RETURN-1', 'reason' => 'Screen broken'];
        $service = app(AfterSales::class);
        $first = $service->receive($id, new ActorContext(910002), 'return-damaged', $input);
        $this->assertEquals($first, $service->receive($id, new ActorContext(910002), 'return-damaged', $input));
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 3, 'available_quantity' => 3]);
        $input['disposition'] = 'sellable';
        $input['reference'] = 'RETURN-2';
        $service->receive($id, new ActorContext(910002), 'return-good', $input);
        $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 4, 'available_quantity' => 4]);
        $this->assertDatabaseCount('payment_adjustments', 0);
        $this->assertDatabaseHas('orders', ['id' => $id, 'payment_status' => 'unpaid']);
        $this->assertSame([], app(InventoryMaintenance::class)->reconcile()['unexplained']);
        $this->expectException(InventoryConflict::class);
        $service->receive($id, new ActorContext(910002), 'return-too-many', array_replace($input, ['reference' => 'RETURN-3']));
    }

    public function test_two_partial_settlements_use_the_exact_collected_cap_and_legacy_paid_flag_is_not_evidence(): void
    {
        $id = $this->shipped();
        DB::table('orders')->where('id', $id)->update(['payment_status' => 'paid']);
        try {
            $this->money($id, 'unknown-paid', ['action' => 'request_refund', 'amount' => 1, 'reason' => 'Legacy flag only']);
            $this->fail('Legacy paid flags must not manufacture payment evidence.');
        } catch (InventoryConflict $error) {
            $this->assertSame('payment_evidence_required', $error->reasonCode);
        }
        DB::table('orders')->where('id', $id)->update(['payment_status' => 'unpaid']);
        $this->money($id, 'collect-partials', ['action' => 'collect_cod', 'amount' => 50000000, 'reference' => 'COD-PARTIALS']);
        foreach ([20000000, 30000000] as $amount) {
            $request = $this->money($id, 'partial-'.$amount, ['action' => 'request_refund', 'amount' => $amount, 'reason' => 'Approved partial']);
            $this->money($id, 'settle-'.$amount, ['action' => 'settle_refund', 'refund_id' => $request['id'], 'reference' => 'BANK-'.$amount, 'reason' => 'Statement verified']);
        }
        $this->assertSame(50000000, (int) DB::table('payment_adjustments')->where('kind', 'refund')->where('status', 'settled')->sum('amount'));
        $this->assertDatabaseCount('inventory_return_receipts', 0);
        $this->expectException(InventoryConflict::class);
        $this->money($id, 'over-final', ['action' => 'request_refund', 'amount' => 1, 'reason' => 'Over cap']);
    }

    public function test_return_write_failure_rolls_back_receipt_operation_and_stock(): void
    {
        $id = $this->shipped();
        $item = DB::table('order_items')->where('order_id', $id)->value('id');
        $before = DB::table('inventory_transactions')->count();
        Event::listen('eloquent.creating: '.InventoryTransaction::class, fn () => throw new \RuntimeException('Synthetic movement failure'));
        try {
            app(AfterSales::class)->receive($id, new ActorContext(910002), 'failed-return', [
                'order_item_id' => $item, 'quantity' => 1, 'disposition' => 'sellable', 'reference' => 'RETURN-FAIL', 'reason' => 'Inspection passed']);
            $this->fail('Failure must propagate.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Synthetic movement failure', $error->getMessage());
            $this->assertDatabaseCount('inventory_return_receipts', 0);
            $this->assertDatabaseCount('inventory_transactions', $before);
            $this->assertDatabaseHas('inventory', ['id' => 910001, 'quantity' => 3]);
        } finally {
            Event::forget('eloquent.creating: '.InventoryTransaction::class);
        }
    }

    public function test_http_contract_validation_and_authorization(): void
    {
        $id = $this->shipped();
        $this->actingAs(User::findOrFail(910001))->getJson('/admin/orders/'.$id.'/after-sales')->assertForbidden();
        $this->actingAs(User::findOrFail(910002))->postJson('/admin/orders/'.$id.'/money', [
            'operation_key' => 'http-collect', 'action' => 'collect_cod', 'amount' => 50000000])->assertUnprocessable()->assertJsonValidationErrors('reference');
        $this->postJson('/admin/orders/'.$id.'/money', [
            'operation_key' => 'http-collect', 'action' => 'collect_cod', 'amount' => 50000000, 'reference' => 'COD-HTTP'])->assertOk()->assertJsonPath('status', 'collected');
        $this->getJson('/admin/orders/'.$id.'/after-sales')->assertOk()->assertJsonCount(1, 'payments')->assertJsonPath('payment_status', 'paid');
        $this->get('/admin/orders/'.$id.'/after-sales')->assertOk()->assertSee('COD-HTTP');
    }
}
