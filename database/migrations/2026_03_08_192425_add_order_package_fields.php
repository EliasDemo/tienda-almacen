<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campos nuevos en stock_request_items
        Schema::table('stock_request_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_request_items', 'package_type')) {
                $table->enum('package_type', ['saco', 'caja'])->default('saco')->after('unit');
            }
            if (!Schema::hasColumn('stock_request_items', 'real_total')) {
                $table->decimal('real_total', 10, 2)->default(0)->after('quantity_sent');
            }
        });

        // Campos nuevos en stock_requests
        Schema::table('stock_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_requests', 'real_total')) {
                $table->decimal('real_total', 10, 2)->default(0)->after('estimated_total');
            }
        });

        // Vincular paquetes a pedidos
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'stock_request_item_id')) {
                $table->foreignId('stock_request_item_id')->nullable()->after('transfer_line_id')
                      ->constrained('stock_request_items')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_request_item_id');
        });
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropColumn('real_total');
        });
        Schema::table('stock_request_items', function (Blueprint $table) {
            $table->dropColumn(['package_type', 'real_total']);
        });
    }
};