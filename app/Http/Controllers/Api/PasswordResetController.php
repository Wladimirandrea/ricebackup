<?php
// app/Http/Controllers/Api/PasswordResetController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Enviar enlace de recuperación al correo
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        // ── Detectar idioma ANTES de enviar el correo ──
        $locale    = $request->header('Accept-Language', 'es');
        $isEnglish = str_starts_with($locale, 'en');
        app()->setLocale($isEnglish ? 'en' : 'es');

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => $isEnglish
                    ? 'We sent a recovery link to your email.'
                    : 'Te enviamos un enlace de recuperación a tu correo.',
            ], 200);
        }

        return response()->json([
            'message' => $isEnglish
                ? 'Could not send the email. Please try again.'
                : 'No pudimos enviar el correo. Intenta de nuevo.',
        ], 500);
    }

    /**
     * Resetear la contraseña con el token del correo
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'es');
        $isEnglish = str_starts_with($locale, 'en');

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => $isEnglish
                    ? 'Password updated successfully. You can now sign in.'
                    : 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.',
            ], 200);
        }

        return response()->json([
            'message' => $isEnglish
                ? 'The reset link is invalid or has expired.'
                : 'El enlace de recuperación es inválido o ha expirado.',
        ], 422);
    }
}
