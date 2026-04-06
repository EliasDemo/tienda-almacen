<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('sku_code')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->enum('sale_unit', ['kg', 'unit']);
            $table->decimal('conversion_factor', 10, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};