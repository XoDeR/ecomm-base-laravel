<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_documents', function (Blueprint $table) {
            $table->id();
            // receipt | write_off | adjustment | order
            $table->string('doc_type', 32);
            $table->string('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 64)->nullable()->index();
            // add | remove | reserve | release | set
            $table->string('event_type', 32);
            $table->integer('qty');
            $table->string('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_log');
        Schema::dropIfExists('stock_documents');
    }
};
