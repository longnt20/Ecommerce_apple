<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_legacy_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->unique()->constrained('inventory')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('reason', 255)->default('unattributed_legacy_reservation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Legacy holds are quarantine evidence; retain them until an approved reconciliation.');
    }
};
