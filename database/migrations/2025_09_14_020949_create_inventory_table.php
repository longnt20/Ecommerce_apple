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
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('product_variant_id')->constrained('product_variants');
            
            // Số lượng
            $table->integer('quantity')->default(0); // Tồn kho thực tế
            $table->integer('available_quantity')->default(0); // Có thể bán (trừ đã đặt)
            $table->integer('reserved_quantity')->default(0); // Đã giữ cho đơn hàng
            $table->integer('incoming_quantity')->default(0); // Đang về kho
            
            // Mức cảnh báo
            $table->integer('min_stock_level')->default(0); // Tồn kho tối thiểu
            $table->integer('max_stock_level')->nullable(); // Tồn kho tối đa
            $table->integer('reorder_point')->default(0); // Điểm đặt hàng lại
            $table->integer('reorder_quantity')->default(0); // Số lượng đặt hàng lại
            
            // Vị trí trong kho
            $table->string('location')->nullable(); // A1-B2-C3
            $table->string('bin_code')->nullable(); // Mã kệ
            
            // Tracking
            $table->timestamp('last_counted_at')->nullable(); // Lần kiểm kê cuối
            $table->timestamp('last_received_at')->nullable(); // Lần nhập cuối
            $table->timestamp('last_shipped_at')->nullable(); // Lần xuất cuối
            
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['warehouse_id', 'product_variant_id']);
            
            // Indexes
            $table->index(['warehouse_id', 'product_variant_id']);
            $table->index('available_quantity');
            $table->index('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
