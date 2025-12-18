<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Mostrar el formulario de login
     */
    public function showLoginForm()
    {
        // Si ya está autenticado, redirigir al dashboard
        if (auth()->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('welcome');
    }

    /**
     * Procesar el login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $identifier = $request->email;
            
            // Intentar encontrar el usuario por email o document_number
            $user = User::where('email', $identifier)
                ->orWhere('document_number', $identifier)
                ->first();

            if (!$user) {
                return back()->with('error', 'Credenciales inválidas')->withInput();
            }

            // Verificar la contraseña
            if (!Hash::check($request->password, $user->password)) {
                return back()->with('error', 'Credenciales inválidas')->withInput();
            }

            // Generar token JWT
            $token = JWTAuth::fromUser($user);

            // Autenticar al usuario en la sesión web
            auth()->login($user);

            // Guardar el token en una cookie para que el middleware pueda leerlo
            $cookie = cookie('admin_token', $token, 60 * 24 * 7); // 7 días

            return redirect()->route('admin.dashboard')->withCookie($cookie);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al iniciar sesión: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout()
    {
        auth()->logout();
        $cookie = cookie()->forget('admin_token');
        
        return redirect('/')->withCookie($cookie)->with('success', 'Sesión cerrada exitosamente');
    }
}





