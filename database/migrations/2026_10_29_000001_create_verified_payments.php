<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->string('reference', 32)->unique();
            $table->string('provider', 16)->default('vnpay');
            $table->string('merchant', 32);
            $table->unsignedBigInteger('amount_vnd');
            $table->char('currency', 3)->default('VND');
            $table->string('status', 32)->default('pending');
            $table->string('provider_transaction', 32)->nullable();
            $table->unique(['provider', 'merchant', 'provider_transaction'], 'payment_provider_transaction_unique');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_attempt_id')->constrained()->restrictOnDelete();
            $table->char('fingerprint', 64)->unique();
            $table->char('response_code', 2);
            $table->char('transaction_status', 2);
            $table->string('outcome', 32);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Paid attempts/events are financial evidence; never auto-delete them.
        throw new RuntimeException('Forward-only payment ledger. Retain tables and disable new online payments for rollback.');
    }
};
