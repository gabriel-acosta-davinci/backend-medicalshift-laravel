<?php

/**
 * Script de prueba para verificar la configuración de Mailpit
 * 
 * Uso: php test-mail.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "========================================\n";
echo "Prueba de Configuración de Mail\n";
echo "========================================\n\n";

// Mostrar configuración actual
echo "Configuración de Mail:\n";
echo "  MAIL_MAILER: " . config('mail.default') . "\n";
echo "  MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "  MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "  MAIL_USERNAME: " . (config('mail.mailers.smtp.username') ?: 'null') . "\n";
echo "  MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "  MAIL_FROM_NAME: " . config('mail.from.name') . "\n";
echo "\n";

// Intentar enviar un email de prueba
try {
    echo "Intentando enviar email de prueba...\n";
    
    Mail::raw('Este es un email de prueba desde Laravel a Mailpit.', function ($message) {
        $message->to('test@example.com')
                ->subject('Prueba de Mailpit');
    });
    
    echo "✓ Email enviado exitosamente!\n";
    echo "\n";
    echo "Ahora verifica en Mailpit (http://localhost:8025) si el email fue capturado.\n";
    
} catch (\Exception $e) {
    echo "✗ Error al enviar email:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "\n";
    echo "Posibles causas:\n";
    echo "  1. Mailpit no está corriendo en Laragon\n";
    echo "  2. La configuración en .env no es correcta\n";
    echo "  3. El puerto 1025 está bloqueado o en uso\n";
    echo "\n";
    echo "Verifica:\n";
    echo "  - Que Mailpit esté iniciado en Laragon\n";
    echo "  - Que el archivo .env tenga la configuración correcta\n";
    echo "  - Que puedas acceder a http://localhost:8025\n";
}

echo "\n========================================\n";

