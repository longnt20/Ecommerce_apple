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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Có thể đánh giá theo sản phẩm hoặc theo biến thể
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('cascade');

            $table->tinyInteger('rating')->comment('1-5 sao');
            $table->text('content')->nullable()->comment('Nội dung đánh giá');
            // lưu danh sách tag trải nghiệm người dùng tick, dạng JSON
            $table->json('tags')->nullable()->comment('Các tiêu chí được chọn: ["Form áo vừa vặn", "Màu sắc tươi sáng"]');

            // lưu trạng thái đánh giá (cho phép admin duyệt)
            $table->enum('status', ['public', 'hidden'])->default('public')->comment('Trạng thái hiển thị');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
