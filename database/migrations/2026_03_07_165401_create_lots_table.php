<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('lot_code')->unique();
            $table->string('supplier')->nullable();
            $table->decimal('purchase_price_per_kg', 10, 2)->nullable();
            $table->decimal('purchase_price_per_unit', 10, 2)->nullable();
            $table->decimal('total_quantity', 12, 3);
            $table->enum('unit', ['kg', 'unit']);
            $table->decimal('remaining_quantity', 12, 3);
            $table->date('entry_date');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};