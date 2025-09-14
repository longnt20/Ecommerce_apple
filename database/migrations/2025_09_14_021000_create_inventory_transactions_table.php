<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique(); // INV-20240101-001
            
            // Relations
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses');
            
            // Transaction type
            $table->enum('type', [
                'import',      // Nhập kho
                'export',      // Xuất kho
                'transfer',    // Chuyển kho
                'adjust',      // Điều chỉnh
                'return',      // Trả hàng
                'damage',      // Hư hỏng
                'reserve',     // Giữ hàng
                'release',     // Hủy giữ hàng
                'inventory'    // Kiểm kê
            ]);
            
            // Quantities
            $table->integer('quantity'); // Số lượng (+/-)
            $table->integer('before_quantity'); // Tồn kho trước
            $table->integer('after_quantity'); // Tồn kho sau
            
            // Reference
            $table->string('reference_type')->nullable(); // order, purchase_order, return
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_code')->nullable(); // Mã đơn hàng, mã PO
            
            // Financial
            $table->decimal('unit_cost', 15, 2)->nullable(); // Giá vốn
            $table->decimal('total_cost', 15, 2)->nullable(); // Tổng giá trị
            
            // Meta data
            $table->string('reason')->nullable(); // Lý do
            $table->text('notes')->nullable();
            $table->json('meta_data')->nullable(); // Dữ liệu bổ sung
            
            // User tracking
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])
                  ->default('completed');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['warehouse_id', 'product_variant_id']);
            $table->index(['type', 'status']);
            $table->index('transaction_code');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
