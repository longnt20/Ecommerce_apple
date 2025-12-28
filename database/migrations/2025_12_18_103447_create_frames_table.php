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
        Schema::create('frames', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên khung (Hot Sale v1, Black Friday...)

            // Ảnh chính
            $table->string('top_background')->nullable(); // Ảnh nền trên
            $table->string('bottom_background')->nullable(); // Ảnh nền dưới
            // Ảnh trang trí
            $table->string('ribbon_image')->nullable();     // Banner tiêu đề
            $table->string('left_decor_image')->nullable(); // Trang trí bên trái
            $table->string('right_decor_image')->nullable();// Trang trí bên phải
            // Trạng thái
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frames');
    }
};
