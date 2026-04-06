<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_credits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('original_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2);
            $table->enum('status', ['pending', 'partial', 'paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('customer_id');
        });

        Schema::create('credit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_credit_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'transfer', 'other'])->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
        Schema::dropIfExists('customer_credits');
    }
};