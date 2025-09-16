<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            // Optional meta columns present in base schema.sql, kept optional here
            if (!Schema::hasColumn('addresses', 'label')) {
                $table->string('label')->nullable();
            }
            if (!Schema::hasColumn('addresses', 'address')) {
                $table->text('address')->nullable();
            }
            if (!Schema::hasColumn('addresses', 'au_gnaf')) {
                $table->string('au_gnaf', 100)->nullable();
            }

            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('suburbcity');
            $table->string('stateprov', 50);
            $table->string('postcode', 20);
            $table->string('country', 2)->default('AU');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};


