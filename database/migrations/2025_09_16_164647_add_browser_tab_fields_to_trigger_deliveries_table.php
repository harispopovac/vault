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
        Schema::table('trigger_deliveries', function (Blueprint $table) {
            $table->string('prompt_token', 100)->nullable()->unique()->after('next_retry_at');
            $table->string('delivery_url', 500)->nullable()->after('prompt_token');
            $table->timestamp('opened_at')->nullable()->after('delivery_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trigger_deliveries', function (Blueprint $table) {
            $table->dropColumn(['prompt_token', 'delivery_url', 'opened_at']);
        });
    }
};
