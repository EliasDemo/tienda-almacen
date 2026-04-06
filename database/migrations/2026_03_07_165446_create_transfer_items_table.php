<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->decimal('merma_kg', 10, 3)->default(0);
            $table->integer('total_packages')->default(0);
            $table->integer('received_packages')->default(0);
            $table->integer('transit_sold_packages')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['transfer_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_lines');
    }
};