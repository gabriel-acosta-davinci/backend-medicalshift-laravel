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
            $table->string('document_number')->unique()->nullable()->after('email');
            $table->string('nombre')->nullable()->after('name');
            $table->string('phone_number')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('cbu')->nullable();
            $table->string('address_street')->nullable();
            $table->integer('address_number')->nullable();
            $table->string('address_floor')->nullable();
            $table->string('address_apartment')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_province')->nullable();
            $table->timestamp('password_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'document_number',
                'nombre',
                'phone_number',
                'marital_status',
                'cbu',
                'address_street',
                'address_number',
                'address_floor',
                'address_apartment',
                'address_city',
                'address_province',
                'password_updated_at',
            ]);
        });
    }
};
