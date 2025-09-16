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
        Schema::create('triggers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('repository_id');
            $table->uuid('prompt_id');
            
            // GitHub Event Configuration
            $table->json('github_events')->default('[]');
            $table->json('event_filters')->default('{}');
            
            // User Targeting
            $table->enum('target_type', ['all', 'specific_users', 'roles'])->default('all');
            $table->json('target_users')->default('[]');
            $table->json('target_roles')->default('[]');
            
            // Webhook Configuration  
            $table->bigInteger('github_webhook_id')->nullable();
            $table->string('webhook_secret');
            $table->boolean('webhook_active')->default(true);
            
            // Settings
            $table->boolean('is_active')->default(true);
            $table->integer('delivery_delay_minutes')->default(0);
            
            $table->foreignId('organization_id')->constrained('organisations')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('repository_id')->references('id')->on('repositories')->cascadeOnDelete();
            $table->foreign('prompt_id')->references('id')->on('prompts')->cascadeOnDelete();
            
            $table->index(['repository_id', 'is_active']);
            $table->index(['organization_id']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triggers');
    }
};
