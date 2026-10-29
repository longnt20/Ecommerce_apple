<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_operations', function (Blueprint $table) {
            $table->ulid('id')->charset('ascii')->collation('ascii_bin')->primary();
            $table->char('operation_hash', 64)->unique();
            $table->char('payload_hash', 64);
            $table->string('kind', 40);
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->json('result')->nullable();
            $table->timestamps();
        });
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status', 24)->index();
            $table->string('source', 16)->default('checkout');
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
        });
        Schema::create('inventory_reservation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('inventory_reservations')->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status', 24);
            $table->timestamps();
            $table->unique(['reservation_id', 'order_item_id', 'warehouse_id'], 'reservation_line_warehouse_unique');
            $table->index(['warehouse_id', 'product_variant_id', 'status'], 'reservation_stock_state_index');
        });
        Schema::create('inventory_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory')->restrictOnDelete();
            $table->foreignUlid('operation_id')->charset('ascii')->collation('ascii_bin')->nullable()->constrained('inventory_operations')->restrictOnDelete();
            $table->integer('recorded_quantity');
            $table->integer('reserved_quantity');
            $table->integer('counted_quantity')->nullable();
            $table->string('reason', 255);
            $table->string('status', 16)->default('open');
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['inventory_id', 'status']);
        });
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignUlid('operation_id')->charset('ascii')->collation('ascii_bin')->nullable()->constrained('inventory_operations')->restrictOnDelete();
            $table->unsignedInteger('operation_line')->nullable();
            $table->unique(['operation_id', 'operation_line'], 'inventory_operation_line_unique');
            $table->index(['reference_type', 'reference_id'], 'inventory_reference_index');
        });
        Schema::table('order_transitions', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Forward-only stock ledger. Stop writers and retain reservations, operations and evidence.');
    }
};
