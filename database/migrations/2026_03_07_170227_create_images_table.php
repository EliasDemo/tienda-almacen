<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('imageable_type');   // App\Models\Product, App\Models\Debt, etc.
            $table->unsignedBigInteger('imageable_id');
            $table->string('path');             // Ruta del archivo en storage
            $table->string('filename');         // Nombre original del archivo
            $table->string('mime_type');        // image/jpeg, image/png, etc.
            $table->unsignedInteger('size')->default(0); // Tamaño en bytes
            $table->string('type')->default('photo');     // photo, label, receipt, etc.
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['imageable_type', 'imageable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};