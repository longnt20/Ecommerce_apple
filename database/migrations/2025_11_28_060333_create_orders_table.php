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
        $table->decimal('final_amount', 12, 2)->default(0);

        // Trạng thái đơn hàng
        $table->enum('status', [
            'pending',      // chưa xử lý
            'confirmed',    // đã xác nhận
            'shipping',     // đang giao
            'completed',    // hoàn thành
            'cancelled'     // hủy
        ])->default('pending');

        $table->string('customer_name');
        $table->string('phone');
        $table->string('address');

        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('orders');
}

};
