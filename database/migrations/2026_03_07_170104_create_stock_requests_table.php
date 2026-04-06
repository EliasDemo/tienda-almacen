<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('request_code')->unique();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('transfer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('request_type', ['restock', 'customer_order']);
            // Si es pedido de cliente
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('advance_amount', 10, 2)->default(0);
            $table->enum('advance_method', ['cash', 'transfer', 'other'])->nullable();
            $table->string('advance_reference')->nullable();
            // Totales
            $table->decimal('estimated_total', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            // Estado
            $table->enum('status', [
                'pending',
                'preparing',
                'dispatched',
                'received',
                'delivered',
                'cancelled',
            ])->default('pending');
            $table->enum('label_color', ['amarillo', 'verde', 'rojo', 'azul'])->default('amarillo');
            // Venta final (se genera cuando cliente recoge)
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_requests');
    }
};
