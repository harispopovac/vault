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
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('name', 15)->nullable();
            $table->char('dark_text', 10)->nullable();
            $table->char('dark_bg', 10)->nullable();
            $table->char('dark_border', 10)->nullable();
            $table->char('light_text', 10)->nullable();
            $table->char('light_bg', 10)->nullable();
            $table->char('light_border', 10)->nullable();

            $table->index('uuid');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
