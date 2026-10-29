<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->char('operation_hash', 64);
            $table->char('payload_hash', 64);
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->string('reason', 500)->nullable();
            $table->uuid('request_id')->nullable();
            $table->timestamp('created_at');
            $table->unique(['order_id', 'operation_hash']);
            // Current transition graph is acyclic: each target can be reached once.
            $table->unique(['order_id', 'to_status']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Forward-only transition history. Disable transitions; retain records and stock movements.');
    }
};
