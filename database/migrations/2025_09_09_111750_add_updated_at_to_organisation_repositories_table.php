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
        if (Schema::hasTable('organisation_repositories')) {
            Schema::table('organisation_repositories', function (Blueprint $table) {
                if (!Schema::hasColumn('organisation_repositories', 'updated_at')) {
                    $table->timestampTz('updated_at')->nullable()->after('created_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('organisation_repositories') && Schema::hasColumn('organisation_repositories', 'updated_at')) {
            Schema::table('organisation_repositories', function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }
    }
};
