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
    Schema::create('order_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('set null');

        $table->string('product_name');
        $table->string('variant_name')->nullable();

        $table->integer('quantity');
        $table->decimal('price', 12, 2);
        $table->decimal('total', 12, 2);

        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('order_items');
}

};
