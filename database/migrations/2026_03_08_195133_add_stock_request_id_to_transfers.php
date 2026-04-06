<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('transfers', 'stock_request_id')) {
            Schema::table('transfers', function (Blueprint $table) {
                $table->foreignId('stock_request_id')->nullable()->after('received_by')
                      ->constrained('stock_requests')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_request_id');
        });
    }
};