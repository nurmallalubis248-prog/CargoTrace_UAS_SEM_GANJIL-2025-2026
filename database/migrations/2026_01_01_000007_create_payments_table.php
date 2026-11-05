<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('order_id', 60)->unique();
            $table->unsignedBigInteger('amount'); // IDR
            $table->string('status', 15)->default('PENDING')->index(); // PENDING | PAID | FAILED | EXPIRED | CANCELLED
            $table->string('payment_type', 30)->nullable(); // qris, bank_transfer, gopay, ...
            $table->string('snap_token')->nullable();
            $table->text('redirect_url')->nullable();
            $table->json('raw_notification')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
