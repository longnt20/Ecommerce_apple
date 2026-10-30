<?php

namespace App\Modules\Inventory\Infrastructure;

use App\Exceptions\InventoryConflict;
use App\Modules\Identity\Contracts\AccountAccess;
use App\Modules\Inventory\Contracts\ReturnedGoods as Contract;
use App\SharedKernel\ActorContext;
use App\SharedKernel\Clock;
use Illuminate\Support\Facades\DB;

final class ReturnedGoods implements Contract
{
    public function __construct(private StockWriter $writer, private AccountAccess $accounts, private Clock $clock) {}

    public function receive(int $orderId, int $itemId, int $warehouseId, int $variantId, int $shippedQuantity, ActorContext $actor, string $key, array $input): array
    {
        $this->writer->assertEnabled();
        if (! $this->accounts->administrator($actor) || DB::transactionLevel() < 1) {
            throw new InventoryConflict('forbidden_actor', 'Nhận hàng trả cần quản trị viên và transaction đơn hàng.');
        }
        $hash = hash('sha256', $actor->id.'|'.$key);
        $payload = hash('sha256', json_encode([$orderId, $itemId, $input], JSON_THROW_ON_ERROR));
        $previous = DB::table('inventory_return_receipts')->where('operation_hash', $hash)->first();
        if ($previous) {
            if (! hash_equals($previous->payload_hash, $payload)) {
                throw new InventoryConflict('operation_payload_conflict', 'Mã thao tác đã được dùng với nội dung khác.');
            }

            return json_decode($previous->result, true, 512, JSON_THROW_ON_ERROR);
        }
        $quantity = $this->writer->store->positive($input['quantity']);
        if (! in_array($input['disposition'], ['sellable', 'damaged'], true)) {
            throw new InventoryConflict('invalid_disposition', 'Cần kết quả kiểm tra hàng trả.');
        }
        $proof = DB::table('inventory_reservation_items')->where('order_item_id', $itemId)->where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $variantId)->where('status', 'committed')->lockForUpdate()->get();
        $received = (int) DB::table('inventory_return_receipts')->where('order_item_id', $itemId)->sum('quantity');
        if ((int) $proof->sum('quantity') !== $shippedQuantity || $quantity > $shippedQuantity - $received) {
            throw new InventoryConflict('return_evidence_conflict', 'Không đủ bằng chứng xuất kho hoặc số lượng trả vượt số đã xuất.');
        }
        $result = ['movement_ids' => [], 'discrepancy_id' => null, 'disposition' => $input['disposition']];
        $id = DB::table('inventory_return_receipts')->insertGetId(['order_id' => $orderId, 'order_item_id' => $itemId,
            'warehouse_id' => $warehouseId, 'product_variant_id' => $variantId, 'actor_id' => $actor->id,
            'operation_hash' => $hash, 'payload_hash' => $payload, 'quantity' => $quantity, 'disposition' => $input['disposition'],
            'reference' => $input['reference'], 'reason' => $input['reason'], 'result' => json_encode($result), 'created_at' => $this->clock->now()]);
        if ($input['disposition'] === 'sellable') {
            $movement = $this->writer->admin('import', $actor->id, 'return:'.$id,
                ['warehouse_id' => $warehouseId, 'variant_id' => $variantId, 'quantity' => $quantity,
                    'reference_type' => 'return_receipt', 'reference_id' => $id, 'reference_code' => $input['reference'], 'reason' => $input['reason']]);
            $result['movement_ids'] = $movement['movement_ids'];
        }
        $result['receipt_id'] = $id;
        DB::table('inventory_return_receipts')->where('id', $id)->update(['result' => json_encode($result, JSON_THROW_ON_ERROR)]);

        return $result;
    }

    public function history(int $orderId): array
    {
        return DB::table('inventory_return_receipts')->where('order_id', $orderId)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }
}
