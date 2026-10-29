<?php

namespace App\Modules\Inventory\Infrastructure;

use App\Exceptions\InventoryConflict;
use App\Models\InventoryOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IdempotentOperation
{
    public function run(string $kind, ?int $actorId, string $key, array $payload, callable $work): array
    {
        if ($key === '' || strlen($key) > 255) {
            throw new InventoryConflict('invalid_operation', 'Mã thao tác không hợp lệ.');
        }
        $operationHash = hash('sha256', json_encode([$kind, $actorId, $key], JSON_THROW_ON_ERROR));
        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($kind, $actorId, $operationHash, $payloadHash, $work) {
            DB::table('inventory_operations')->insertOrIgnore([
                'id' => (string) Str::ulid(), 'kind' => $kind, 'actor_id' => $actorId,
                'operation_hash' => $operationHash, 'payload_hash' => $payloadHash,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $operation = InventoryOperation::where('operation_hash', $operationHash)->lockForUpdate()->firstOrFail();
            if (! hash_equals($operation->payload_hash, $payloadHash)) {
                throw new InventoryConflict('operation_payload_conflict', 'Mã thao tác đã được dùng với nội dung khác.');
            }
            if ($operation->result !== null) {
                return $operation->result;
            }
            $result = $work($operation->id);
            $operation->update(['result' => $result]);

            return $result;
        }, 3);
    }
}
