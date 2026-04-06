<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->enum('price_type', ['minorista', 'mayorista']);
            $table->decimal('price', 10, 2);
            $table->decimal('min_quantity', 12, 3)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_variant_id', 'price_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
