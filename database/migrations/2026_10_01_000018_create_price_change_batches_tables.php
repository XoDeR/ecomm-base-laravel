<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_change_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            // percent | fixed | currency
            $table->string('mode', 20);
            // Calculation parameters (percentage, amount, ...)
            $table->json('mode_params')->nullable();
            $table->foreignId('scope_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('scope_include_subcategories')->default(false);
            $table->string('scope_vendor')->nullable();
            $table->unsignedInteger('affected_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('price_change_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('price_change_batches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_price', 10, 2);
            $table->decimal('new_price', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_change_batch_items');
        Schema::dropIfExists('price_change_batches');
    }
};
