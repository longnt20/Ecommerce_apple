<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('code')->unique(); // Mã đơn hàng
        $table->decimal('total_price', 12, 2)->default(0);
        $table->decimal('shipping_fee', 12, 2)->default(0);
        $table->decimal('discount_amount', 12, 2)->default(0);
        $table->decimal('final_amount', 12, 2)->default(0);
        $table->enum('payment_method', [
            'COD',      
            'VNPAY',   
            'MOMO',   
        ])->default('COD');
        $table->enum('payment_status', [
            'unpaid',      
            'paid',   
            'refunded',   
        ])->default('unpaid');
        // Trạng thái đơn hàng
        $table->enum('status', [
            'pending',      // chưa xử lý
            'confirmed',    // đã xác nhận
            'shipping',     // đang giao
            'completed',    // hoàn thành
            'cancelled'     // hủy
        ])->default('pending');
        $table->string('transaction_id',255)->nullable();
        // Địa chỉ giao hàng
            $table->string('fullname')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('ward')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->text('note')->nullable();

            // Thời gian
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('orders');
}

};
