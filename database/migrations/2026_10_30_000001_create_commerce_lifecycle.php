<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_adjustments')) {
            Schema::create('payment_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->restrictOnDelete();
                $table->string('kind', 16);
                $table->string('status', 16);
                $table->unsignedBigInteger('amount');
                $table->char('operation_hash', 64)->unique();
                $table->char('payload_hash', 64);
                $table->string('reference', 128)->nullable();
                $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
                $table->string('reason', 500)->nullable();
                $table->timestamps();
                $table->unique(['kind', 'reference']);
                $table->index(['order_id', 'kind', 'status']);
            });
        }
        if (! Schema::hasTable('payment_adjustment_events')) {
            Schema::create('payment_adjustment_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('adjustment_id')->constrained('payment_adjustments')->restrictOnDelete();
                $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
                $table->char('operation_hash', 64)->unique();
                $table->char('payload_hash', 64);
                $table->string('status', 16);
                $table->string('reference', 128)->nullable();
                $table->string('reason', 500)->nullable();
                $table->timestamp('created_at');
            });
        }
        if (! Schema::hasColumn('coupons', 'assignment_revision')) {
            Schema::table('coupons', fn (Blueprint $table) => $table->unsignedBigInteger('assignment_revision')->default(0));
        }
        if (! Schema::hasTable('coupon_redemptions')) {
            Schema::create('coupon_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
                $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->foreignId('assignment_id')->constrained('coupon_uses')->restrictOnDelete();
                $table->string('status', 16);
                $table->unsignedBigInteger('discount_amount');
                $table->json('snapshot');
                $table->timestamps();
                $table->index(['coupon_id', 'status']);
                $table->index(['assignment_id', 'status']);
            });
        }
        if (! Schema::hasTable('coupon_assignment_deliveries')) {
            Schema::create('coupon_assignment_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assignment_id')->unique()->constrained('coupon_uses')->restrictOnDelete();
                $table->string('status', 16);
                $table->timestamp('updated_at');
            });
        }
        foreach (['revoked_at', 'notified_at'] as $column) {
            if (! Schema::hasColumn('coupon_uses', $column)) {
                Schema::table('coupon_uses', fn (Blueprint $table) => $table->timestamp($column)->nullable());
            }
        }
        if (! Schema::hasTable('inventory_return_receipts')) {
            Schema::create('inventory_return_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->restrictOnDelete();
                $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
                $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
                $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
                $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
                $table->char('operation_hash', 64)->unique();
                $table->char('payload_hash', 64);
                $table->unsignedInteger('quantity');
                $table->string('disposition', 16);
                $table->string('reference', 128);
                $table->string('reason', 500);
                $table->json('result');
                $table->timestamp('created_at');
                $table->index(['order_item_id', 'disposition']);
                $table->unique(['order_item_id', 'reference'], 'return_item_reference_unique');
            });
        }
        if (! Schema::hasIndex('inventory_return_receipts', 'return_item_reference_unique')) {
            Schema::table('inventory_return_receipts', fn (Blueprint $table) => $table->unique(['order_item_id', 'reference'], 'return_item_reference_unique'));
        }
        if (! Schema::hasTable('inventory_documents')) {
            Schema::create('inventory_documents', function (Blueprint $table) {
                $table->id();
                $table->ulid('operation_id')->charset('ascii')->collation('ascii_bin')->unique();
                $table->string('kind', 32);
                $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
                $table->string('status', 32);
                $table->json('payload');
                $table->timestamp('created_at');
                $table->foreign('operation_id')->references('id')->on('inventory_operations')->restrictOnDelete();
            });
        }
        if (! Schema::hasTable('inventory_document_lines')) {
            Schema::create('inventory_document_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('inventory_documents')->restrictOnDelete();
                $table->unsignedInteger('line');
                $table->json('result');
                $table->unique(['document_id', 'line']);
            });
        }
        if (! Schema::hasTable('inventory_settings')) {
            Schema::create('inventory_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->unsignedInteger('low_stock_threshold');
                $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
                $table->timestamp('updated_at');
            });
        }
    }

    public function down(): void
    {
        // Never erase new financial/stock history during a code rollback.
        foreach (['coupon_assignment_deliveries', 'payment_adjustments', 'payment_adjustment_events', 'coupon_redemptions', 'inventory_return_receipts', 'inventory_documents', 'inventory_document_lines', 'inventory_settings'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('P6 contains business records; retain additive schema and roll forward.');
            }
        }
        foreach (['coupon_assignment_deliveries', 'inventory_document_lines', 'inventory_documents', 'inventory_return_receipts', 'inventory_settings', 'coupon_redemptions', 'payment_adjustment_events', 'payment_adjustments'] as $table) {
            Schema::dropIfExists($table);
        }
        // Keep nullable assignment metadata: rolling down must not discard delivery/revocation evidence.
    }
};
