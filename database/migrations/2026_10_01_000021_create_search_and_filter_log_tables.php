<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Popular search queries, used for autocomplete suggestions and analytics
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('query')->unique();
            $table->unsignedInteger('results_count')->default(0);
            $table->unsignedInteger('search_count')->default(1);
            $table->timestamp('last_searched')->useCurrent();
        });

        Schema::create('filter_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->json('filters')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filter_history');
        Schema::dropIfExists('search_queries');
    }
};
