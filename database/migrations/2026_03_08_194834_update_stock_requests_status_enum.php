<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_requests MODIFY COLUMN status ENUM('pending','confirmed','preparing','dispatched','received','ready','delivered','cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_requests MODIFY COLUMN status ENUM('pending','preparing','dispatched','received','delivered','cancelled') DEFAULT 'pending'");
    }
};