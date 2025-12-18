<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * Verificar el email del usuario
     * GET /api/email/verify/{id}/{hash}
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Verificar que el hash coincida
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect(config('app.frontend_url', 'http://localhost:5173') . '/verificacion/error');
        }

        // Si el email ya está verificado, redirigir a éxito
        if ($user->hasVerifiedEmail()) {
            return redirect(config('app.frontend_url', 'http://localhost:5173') . '/verificacion/exito');
        }

        // Marcar el email como verificado
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Redirigir a la página de éxito
        return redirect(config('app.frontend_url', 'http://localhost:5173') . '/verificacion/exito');
    }

    /**
     * Reenviar email de verificación (API)
     * POST /api/email/resend
     */
    public function resend(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'Usuario no autenticado'
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Tu email ya está verificado'
            ], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Se ha reenviado el email de verificación'
        ]);
    }
}
