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
        // Migrar datos de nombre a name si name está vacío o null
        \DB::statement("UPDATE users SET name = nombre WHERE (name IS NULL OR name = '') AND nombre IS NOT NULL AND nombre != ''");
        
        // Ahora eliminar la columna nombre
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre')->nullable()->after('name');
        });
        
        // Migrar datos de name a nombre
        \DB::statement("UPDATE users SET nombre = name WHERE name IS NOT NULL AND name != ''");
    }
};
