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
        Schema::create('trigger_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('trigger_id');
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            
            // GitHub Event Data
            $table->string('github_event_type', 50);
            $table->json('github_payload');
            $table->string('github_delivery_id')->nullable();
            
            // Delivery Status
            $table->enum('status', ['pending', 'delivered', 'responded', 'failed'])->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Response Data
            $table->json('response_data')->nullable();
            $table->uuid('knowledge_entry_id')->nullable();
            
            // Metadata
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('trigger_id')->references('id')->on('triggers')->cascadeOnDelete();
            $table->foreign('knowledge_entry_id')->references('id')->on('knowledge_entries')->nullOnDelete();
            
            $table->index(['trigger_id', 'status']);
            $table->index(['target_user_id', 'status']);
            $table->index(['github_event_type']);
            $table->index(['delivered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trigger_deliveries');
    }
};
