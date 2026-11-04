<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 100)->nullable();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            // Free-text value (for text / range attributes)
            $table->string('value')->nullable();
            $table->foreignId('attribute_option_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price_modifier', 10, 2)->default(0);
            $table->enum('price_operation', ['+', '-'])->default('+');
            $table->integer('stock_quantity')->nullable();
            // Customer picks this option when adding the product to the cart
            $table->boolean('is_selectable')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
