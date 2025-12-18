<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RequestLog;
use Illuminate\Support\Facades\Log;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Obtener información de la request
        $method = $request->method();
        $path = $request->path();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $userId = auth()->id();

        // Obtener body de la request (solo para métodos que lo permiten)
        $requestBody = null;
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $requestBody = json_encode($request->except(['password', 'password_confirmation']));
        }

        // Procesar la request
        $response = $next($request);

        // Calcular tiempo de respuesta
        $responseTime = (microtime(true) - $startTime) * 1000; // en milisegundos

        // Obtener información de la respuesta
        $statusCode = $response->getStatusCode();
        
        // Obtener body de la respuesta (solo para respuestas JSON)
        $responseBody = null;
        if ($response->headers->get('Content-Type') === 'application/json') {
            $content = $response->getContent();
            if ($content) {
                $responseBody = $content;
            }
        }

        // Guardar en base de datos de forma asíncrona (evitar bloquear la respuesta)
        try {
            // Solo loguear requests a la API (no a rutas admin ni assets)
            if (str_starts_with($path, 'api/') && !str_starts_with($path, 'api/admin/')) {
                RequestLog::create([
                    'method' => $method,
                    'path' => $path,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'user_id' => $userId,
                    'status_code' => $statusCode,
                    'response_time' => (int)$responseTime,
                    'request_body' => $requestBody,
                    'response_body' => $responseBody ? substr($responseBody, 0, 5000) : null, // Limitar tamaño
                ]);
            }
        } catch (\Exception $e) {
            // Si falla el logging, no afectar la respuesta
            Log::error('Error al loguear request: ' . $e->getMessage());
        }

        return $response;
    }
}
