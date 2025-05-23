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
        Schema::table('item_usage_stats', function (Blueprint $table) {
            $table->dropColumn(['tags', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_usage_stats', function (Blueprint $table) {
            $table->json('tags')->nullable();
            $table->string('category')->nullable();
        });
    }
};
