<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Promotional price, must be strictly lower than products.price
            $table->decimal('discount_price', 10, 2);
            // NULL start = effective immediately, NULL end = no expiry
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Stock of one selectable variant; NULL = stock of the product itself
            $table->foreignId('product_attribute_id')->nullable()->constrained('product_attributes')->cascadeOnDelete();
            $table->string('sku', 100)->nullable()->index();
            $table->integer('quantity')->default(0);
            $table->integer('reserved')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'product_attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
        Schema::dropIfExists('product_discounts');
    }
};
