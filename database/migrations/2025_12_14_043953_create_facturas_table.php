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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('estado'); // 'Pagada' o 'Pendiente'
            $table->string('periodo'); // Formato: "YYYY-MM"
            $table->decimal('monto', 10, 2)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'estado']);
            $table->index('periodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
