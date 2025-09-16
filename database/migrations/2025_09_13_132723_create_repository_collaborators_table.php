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
        Schema::create('repository_collaborators', function (Blueprint $table) {
            $table->id();
            $table->uuid('repository_id');
            $table->bigInteger('github_user_id');
            $table->string('github_username');
            $table->text('github_avatar_url')->nullable();
            $table->string('github_email')->nullable();
            $table->enum('permission_level', ['read', 'write', 'admin'])->default('read');
            
            // Knowledge Vault role assignment (managed by admins)
            $table->unsignedInteger('vault_role_id')->nullable();
            
            // Sync tracking
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->useCurrent();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('repository_id')->references('id')->on('repositories')->onDelete('cascade');
            $table->foreign('vault_role_id')->references('id')->on('roles');
            
            // Unique constraint per repository
            $table->unique(['repository_id', 'github_user_id']);
            
            // Indexes
            $table->index('repository_id');
            $table->index('github_user_id');
            $table->index('vault_role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repository_collaborators');
    }
};
