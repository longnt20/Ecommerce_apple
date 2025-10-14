<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            
            // Liên kết đến cả product hoặc variant
            $table->unsignedBigInteger('item_id');
            $table->string('item_type'); // 'product' hoặc 'variant'

            $table->timestamps();

            // Index để truy vấn nhanh
            $table->index(['item_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_items');
    }
};
