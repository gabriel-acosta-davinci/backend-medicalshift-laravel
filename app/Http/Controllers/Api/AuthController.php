<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Registrar un nuevo usuario
     * POST /auth/signup
     */
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name' => 'nullable|string',
            'document_number' => 'nullable|string|unique:users,document_number',
            'date_of_birth' => 'nullable|date',
            'associate_number' => 'nullable|string',
            'plan' => 'nullable|in:Plan Bronce,Plan Plata,Plan Oro,Plan Platino',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Error de validación',
                'messages' => $validator->errors()
            ], 400);
        }

        try {
            $userData = $request->all();
            $userData['password'] = Hash::make($request->password);
            $userData['name'] = $request->name ?? 'Usuario';

            // Remover address de userData si existe (se manejará por separado)
            $addressData = null;
            if ($request->has('address') && is_array($request->address)) {
                $addressData = $request->address;
            }

            $user = User::create($userData);
            $user->password_updated_at = now();
            $user->save();

            // Crear dirección si se proporcionó
            if ($addressData) {
                $user->address()->create([
                    'street' => $addressData['street'] ?? null,
                    'number' => $addressData['number'] ?? null,
                    'floor' => $addressData['floor'] ?? null,
                    'apartment' => $addressData['apartment'] ?? null,
                    'city' => $addressData['city'] ?? null,
                    'province' => $addressData['province'] ?? null,
                ]);
            }

            // Cargar la relación de dirección para la respuesta
            $user->load('address');

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'token' => $token,
                'user' => $user->makeHidden(['password', 'remember_token'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login personalizado con email/documentNumber y contraseña
     * POST /auth/login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'password' => 'required|string',
            'identifierType' => 'nullable|in:email,documentNumber',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Email/documentNumber y contraseña son requeridos'
            ], 400);
        }

        try {
            $identifier = $request->identifier;
            $identifierType = $request->identifierType ?? 'email';
            $password = $request->password;

            // Buscar usuario por email o documentNumber
            $user = null;
            if ($identifierType === 'documentNumber') {
                $user = User::where('document_number', $identifier)->first();
            } else {
                $user = User::where('email', $identifier)->first();
            }

            if (!$user) {
                return response()->json([
                    'error' => 'Credenciales inválidas'
                ], 401);
            }

            // Verificar que el usuario tenga contraseña
            if (!$user->password) {
                return response()->json([
                    'error' => 'Usuario no tiene contraseña configurada'
                ], 401);
            }

            // Verificar contraseña
            if (!Hash::check($password, $user->password)) {
                return response()->json([
                    'error' => 'Credenciales inválidas'
                ], 401);
            }

            // Generar token JWT
            $token = JWTAuth::fromUser($user);

            // Activar y generar token digital de 3 dígitos
            $user->activateDigitalToken();

            // Recargar el usuario para incluir el token digital en la respuesta
            $user->refresh();

            return response()->json([
                'message' => 'Login exitoso',
                'token' => $token,
                'user' => $user->makeHidden(['password', 'remember_token']),
                'digitalToken' => $user->digital_token
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Error al generar token',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al iniciar sesión',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar token de autenticación
     * POST /auth/verify
     */
    public function verifyToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idToken' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Token requerido'
            ], 400);
        }

        try {
            $token = $request->idToken;
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'error' => 'Token inválido'
                ], 401);
            }

            return response()->json([
                'message' => 'Token válido',
                'user' => $user->makeHidden(['password', 'remember_token'])
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Token inválido',
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Obtener información del usuario autenticado
     * GET /auth/me
     */
    public function getCurrentUser(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            // Cargar la relación de dirección
            $user->load('address');

            return response()->json(
                $user->makeHidden(['password', 'remember_token'])
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener información del usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Solicitar recuperación de contraseña
     * POST /auth/recovery
     */
    public function recovery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Email requerido'
            ], 400);
        }

        // Por ahora, solo confirmamos que el proceso inició
        // En producción, aquí se enviaría un email con link de recuperación
        return response()->json([
            'message' => 'Si el email existe, se enviarán instrucciones de recuperación'
        ]);
    }

    /**
     * Enviar email de verificación
     * POST /auth/verify-email
     */
    public function sendVerificationEmail(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            // Si el email ya está verificado, no es necesario enviar otro
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'message' => 'Tu email ya está verificado'
                ], 200);
            }

            // Refrescar el usuario desde la base de datos
            $user->refresh();

            // Enviar el email de verificación
            $user->sendEmailVerificationNotification();

            \Log::info('Email de verificación enviado', [
                'user_id' => $user->id,
                'email' => $user->email,
                'mail_config' => [
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                ]
            ]);

            return response()->json([
                'message' => 'Se ha enviado un email de verificación a ' . $user->email
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al enviar email de verificación', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'mail_config' => [
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                ]
            ]);

            return response()->json([
                'error' => 'Error al enviar email de verificación',
                'message' => $e->getMessage(),
                'details' => 'Verifica que Mailpit esté corriendo y que la configuración de mail en .env sea correcta'
            ], 500);
        }
    }

    /**
     * Obtener notificaciones del usuario (estado de verificación de email)
     * GET /auth/notifications
     */
    public function getNotifications(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            $notifications = [];

            // Notificación de email no verificado
            if (!$user->hasVerifiedEmail()) {
                $notifications[] = [
                    'id' => 'email-not-verified',
                    'type' => 'warning',
                    'title' => 'Email no verificado',
                    'message' => 'Por favor, verifica tu dirección de email para completar tu registro.',
                    'action' => 'Verificar email',
                    'actionUrl' => '/dashboard/perfil/seguridad'
                ];
            }

            return response()->json([
                'notifications' => $notifications,
                'unreadCount' => count($notifications)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener notificaciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restablecer contraseña (flujo de recovery desde frontend)
     * POST /auth/reset-password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'newPassword' => 'required|min:6',
            'identifierType' => 'nullable|in:email,documentNumber',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'identifier y newPassword son requeridos'
            ], 400);
        }

        try {
            $identifier = $request->identifier;
            $identifierType = $request->identifierType ?? 'email';
            $newPassword = $request->newPassword;

            // Buscar usuario
            $user = null;
            if ($identifierType === 'documentNumber') {
                $user = User::where('document_number', $identifier)->first();
            } else {
                $user = User::where('email', $identifier)->first();
            }

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no encontrado'
                ], 404);
            }

            // Actualizar contraseña
            $user->password = Hash::make($newPassword);
            $user->password_updated_at = now();
            $user->save();

            return response()->json([
                'message' => 'Contraseña restablecida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo restablecer la contraseña',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Actualizar contraseña (requiere autenticación)
     * PUT /auth/password
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:6',
            'confirmPassword' => 'required|string|same:newPassword',
        ], [
            'currentPassword.required' => 'La contraseña actual es requerida',
            'newPassword.required' => 'La nueva contraseña es requerida',
            'newPassword.min' => 'La nueva contraseña debe tener al menos 6 caracteres',
            'confirmPassword.required' => 'La confirmación de contraseña es requerida',
            'confirmPassword.same' => 'Las contraseñas no coinciden',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Error de validación',
                'messages' => $validator->errors()
            ], 400);
        }

        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar que la contraseña actual sea correcta
            if (!Hash::check($request->currentPassword, $user->password)) {
                return response()->json([
                    'error' => 'La contraseña actual es incorrecta'
                ], 400);
            }

            // Verificar que la nueva contraseña sea diferente a la actual
            if (Hash::check($request->newPassword, $user->password)) {
                return response()->json([
                    'error' => 'La nueva contraseña debe ser diferente a la contraseña actual'
                ], 400);
            }

            // Actualizar la contraseña
            $user->password = Hash::make($request->newPassword);
            $user->password_updated_at = now();
            $user->save();

            return response()->json([
                'message' => 'Contraseña actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar contraseña',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar sesión (requiere autenticación)
     * POST /auth/logout
     */
    public function logout(Request $request)
    {
        try {
            $user = auth()->user();

            if ($user) {
                // Desactivar el token digital
                $user->deactivateDigitalToken();
                
                // Invalidar el token JWT
                JWTAuth::invalidate(JWTAuth::getToken());
            }

            return response()->json([
                'message' => 'Sesión cerrada exitosamente'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Error al cerrar sesión',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cerrar sesión',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el token digital actual del usuario autenticado
     * GET /auth/digital-token
     */
    public function getDigitalToken(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            // Refrescar el usuario desde la base de datos para obtener el token más reciente
            // Esto es importante porque el daemon actualiza el token cada 30 segundos
            $user->refresh();

            // Si el token no está activo, activarlo
            if (!$user->digital_token_active) {
                $user->activateDigitalToken();
                // Refrescar nuevamente después de activar
                $user->refresh();
            }

            // Asegurarse de obtener el valor más reciente del token
            // Cargar directamente desde la base de datos para evitar caché
            $freshUser = User::find($user->id);
            
            return response()->json([
                'digitalToken' => $freshUser->digital_token,
                'active' => $freshUser->digital_token_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener token digital',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
