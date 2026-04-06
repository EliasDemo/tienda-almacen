<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('lot_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('transfer_line_id')->nullable();
            $table->enum('package_type', ['saco', 'caja']);
            $table->decimal('gross_weight', 12, 3)->nullable();
            $table->integer('unit_count')->nullable();
            $table->decimal('net_weight', 12, 3)->nullable();
            $table->integer('net_units')->nullable();
            $table->enum('status', ['closed', 'opened', 'sold', 'exhausted', 'sold_in_transit'])->default('closed');
            $table->enum('location', ['almacen', 'transito', 'tienda'])->default('transito');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
