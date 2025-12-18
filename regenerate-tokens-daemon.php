<?php

/**
 * Script daemon para regenerar tokens digitales cada 30 segundos
 * 
 * Uso: php regenerate-tokens-daemon.php
 * 
 * Este script debe ejecutarse en segundo plano para regenerar los tokens
 * cada 30 segundos. Para producción, se recomienda usar supervisor o
 * un proceso manager similar.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use App\Models\User;

echo "========================================\n";
echo "Regenerador de Tokens Digitales\n";
echo "========================================\n";
echo "Iniciando daemon de regeneración de tokens digitales...\n";
echo "Este proceso regenerará los tokens cada 30 segundos\n";
echo "Presiona Ctrl+C para detener\n";
echo "========================================\n\n";

$iteration = 0;

while (true) {
    $iteration++;
    $startTime = microtime(true);
    
    try {
        // Obtener todos los usuarios con token activo
        $users = User::where('digital_token_active', true)->get();
        
        $count = 0;
        foreach ($users as $user) {
            $user->regenerateDigitalToken();
            $count++;
        }
        
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        if ($count > 0) {
            echo "[" . date('Y-m-d H:i:s') . "] Iteración #{$iteration} - Se regeneraron {$count} token(s) digital(es) en {$executionTime}ms\n";
        } else {
            // Solo mostrar cada 10 iteraciones si no hay usuarios activos para evitar spam
            if ($iteration % 10 === 0) {
                echo "[" . date('Y-m-d H:i:s') . "] Iteración #{$iteration} - No hay usuarios con token activo\n";
            }
        }
    } catch (\Exception $e) {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR en iteración #{$iteration}: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
    // Esperar 30 segundos antes de la siguiente ejecución
    sleep(30);
}

