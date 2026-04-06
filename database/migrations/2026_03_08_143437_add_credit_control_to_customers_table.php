<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('credit_blocked')->default(false)->after('is_active');
            $table->decimal('credit_limit', 10, 2)->default(0)->after('credit_blocked');
            $table->string('block_reason')->nullable()->after('credit_limit');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['credit_blocked', 'credit_limit', 'block_reason']);
        });
    }
};