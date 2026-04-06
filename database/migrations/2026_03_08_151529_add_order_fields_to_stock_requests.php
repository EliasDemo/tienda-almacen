<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_requests', 'delivery_date')) {
                $table->date('delivery_date')->nullable()->after('status');
            }
            if (!Schema::hasColumn('stock_requests', 'customer_notes')) {
                $table->text('customer_notes')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('stock_requests', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('stock_requests', 'preparing_at')) {
                $table->timestamp('preparing_at')->nullable()->after('confirmed_at');
            }
            if (!Schema::hasColumn('stock_requests', 'ready_at')) {
                $table->timestamp('ready_at')->nullable()->after('preparing_at');
            }
            if (!Schema::hasColumn('stock_requests', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('ready_at');
            }
            if (!Schema::hasColumn('stock_requests', 'confirmed_by')) {
                $table->foreignId('confirmed_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_requests', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('confirmed_by');
            }
            if (!Schema::hasColumn('stock_requests', 'cash_register_id')) {
                $table->foreignId('cash_register_id')->nullable()->after('requested_by')->constrained()->nullOnDelete();
            }
        });

        // Tabla de pagos del pedido
        if (!Schema::hasTable('stock_request_payments')) {
            Schema::create('stock_request_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->decimal('amount', 10, 2);
                $table->enum('method', ['cash', 'transfer', 'other'])->default('cash');
                $table->enum('payment_type', ['advance', 'final', 'refund'])->default('advance');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_request_payments');

        Schema::table('stock_requests', function (Blueprint $table) {
            $columns = ['delivery_date', 'customer_notes', 'confirmed_at', 'preparing_at', 
                        'ready_at', 'cancelled_at', 'confirmed_by', 'cancel_reason', 'cash_register_id'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('stock_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
