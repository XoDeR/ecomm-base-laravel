<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_methods', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['shipping', 'payment']);
            $table->string('code', 50);
            $table->string('name');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_test_mode')->default(true);
            // Method specific configuration (shipping cost, pickup address, API keys, ...)
            $table->json('settings')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_methods');
    }
};
