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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'address_street',
                'address_number',
                'address_floor',
                'address_apartment',
                'address_city',
                'address_province',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address_street')->nullable();
            $table->integer('address_number')->nullable();
            $table->string('address_floor')->nullable();
            $table->string('address_apartment')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_province')->nullable();
        });
    }
};
