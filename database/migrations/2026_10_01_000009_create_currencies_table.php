<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            // ISO code (USD, EUR, ...)
            $table->string('code', 3)->unique();
            // Display symbol ($, €, ...)
            $table->string('symbol', 10);
            // Exchange rate relative to the base currency
            $table->decimal('rate', 10, 4)->default(1);
            // Currency shown on the storefront
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
