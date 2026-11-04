<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->foreignId('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->string('customer_email');
            // Chosen methods (rows in shop_methods) plus their names at order time
            $table->foreignId('delivery_id')->nullable()->constrained('shop_methods')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('shop_methods')->nullOnDelete();
            $table->string('delivery_method', 50)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_warehouse')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('status', 50)->default('new')->index();
            $table->string('tracking_number', 100)->nullable();
            $table->text('comment')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot of the product at order time
            $table->string('product_name');
            $table->string('sku', 100)->nullable();
            $table->json('selected_options')->nullable();
            $table->unsignedInteger('qty');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50);
            $table->string('tracking_number', 100)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();
        });

        Schema::create('promo_code_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained()->cascadeOnDelete();
            // NULL for guest orders
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Guest session id, to limit repeated use without registration
            $table->string('session_id', 128)->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_usages');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
