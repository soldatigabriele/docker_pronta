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
        Schema::create('list_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reusable_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('shared_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_with_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('permission_level', ['view', 'edit', 'admin'])->default('view');
            $table->boolean('is_accepted')->default(false);
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // For temporary shares
            $table->boolean('can_share')->default(false); // Can this user share with others
            $table->timestamps();
            
            // Ensure no duplicate shares
            $table->unique(['reusable_list_id', 'shared_with_user_id']);
            $table->index(['shared_with_user_id', 'is_accepted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_shares');
    }
};
