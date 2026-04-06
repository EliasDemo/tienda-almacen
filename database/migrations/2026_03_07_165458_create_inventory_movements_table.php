<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('location', ['almacen', 'tienda']);
            $table->enum('movement_type', [
                'IN',
                'TRANSFER_OUT',
                'TRANSFER_IN',
                'TRANSIT_SALE',
                'OPENING_MERMA',
                'SALE',
                'MERMA',
                'ADJUSTMENT',
            ]);
            $table->decimal('quantity', 12, 3);
            $table->enum('unit', ['kg', 'unit']);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['product_variant_id', 'location']);
            $table->index(['package_id']);
            $table->index(['movement_type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};