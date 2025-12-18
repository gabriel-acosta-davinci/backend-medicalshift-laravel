<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si ya está autenticado en la sesión web, continuar
        if (auth()->check()) {
            return $next($request);
        }

        // Intentar autenticar con JWT si viene el token en cookie
        $token = $request->cookie('admin_token');
        
        if ($token) {
            try {
                // Establecer el token para JWTAuth
                \Tymon\JWTAuth\Facades\JWTAuth::setToken($token);
                $user = \Tymon\JWTAuth\Facades\JWTAuth::authenticate();
                
                if ($user) {
                    // Autenticar al usuario en la sesión web
                    auth()->login($user);
                    return $next($request);
                }
            } catch (\Exception $e) {
                // Token inválido, continuar con la verificación normal
            }
        }

        // Verificar que el usuario esté autenticado
        if (!auth()->check()) {
            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            // Redirigir a la página de login
            return redirect()->route('admin.login.form')->with('error', 'Debes iniciar sesión para acceder al panel de administración');
        }

        // Verificar que el usuario sea administrador
        if (!auth()->user()->is_admin) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'No tienes permiso para acceder al panel de administración'], 403);
            }
            return redirect()->route('admin.login.form')->with('error', 'No tienes permiso para acceder al panel de administración');
        }

        return $next($request);
    }
}
