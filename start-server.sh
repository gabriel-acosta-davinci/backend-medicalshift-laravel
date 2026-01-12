#!/bin/bash

echo "========================================"
echo "Iniciando servidor Laravel con regenerador de tokens"
echo "========================================"
echo ""
echo "Iniciando servidor en http://localhost:8000"
echo "Iniciando regenerador de tokens cada 30 segundos"
echo ""
echo "Presiona Ctrl+C para detener ambos procesos"
echo "========================================"
echo ""

# Función para limpiar procesos al salir
cleanup() {
    echo ""
    echo "Deteniendo procesos..."
    kill $TOKEN_PID 2>/dev/null
    kill $SERVER_PID 2>/dev/null
    echo "Servidor y regenerador de tokens detenidos."
    exit 0
}

# Configurar trap para limpiar al recibir señales
trap cleanup SIGINT SIGTERM

# Iniciar el daemon de tokens en segundo plano
php regenerate-tokens-daemon.php &
TOKEN_PID=$!

# Esperar un momento para que el daemon inicie
sleep 2

# Iniciar el servidor Laravel
php artisan serve &
SERVER_PID=$!

# Esperar a que ambos procesos terminen
wait




