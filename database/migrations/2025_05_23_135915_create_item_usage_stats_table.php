<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_usage_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('item_title'); // Normalized item title for matching
            $table->string('item_title_hash'); // Hash for fast lookups
            $table->json('tags')->nullable(); // Associated tags
            $table->string('category')->nullable();
            $table->integer('usage_count')->default(1);
            $table->integer('completion_count')->default(0);
            $table->timestamp('first_used_at')->useCurrent();
            $table->timestamp('last_used_at')->useCurrent();
            $table->decimal('completion_rate', 5, 2)->default(0); // Percentage completion rate
            $table->timestamps();
            
            // Indexes for fast autocomplete lookups
            $table->index(['user_id', 'item_title_hash']);
            $table->index(['user_id', 'usage_count']);
            $table->index(['user_id', 'completion_rate']);
            $table->unique(['user_id', 'item_title_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_usage_stats');
    }
};
