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
            // Agregar fecha de nacimiento
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('phone_number');
            }
            // Agregar número de asociado
            if (!Schema::hasColumn('users', 'associate_number')) {
                $table->string('associate_number')->nullable()->after('cbu');
            }
            // Agregar plan de obra social
            if (!Schema::hasColumn('users', 'plan')) {
                $table->enum('plan', ['Plan Bronce', 'Plan Plata', 'Plan Oro', 'Plan Platino'])
                      ->nullable()
                      ->after('associate_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_birth',
                'associate_number',
                'plan',
            ]);
        });
    }
};
