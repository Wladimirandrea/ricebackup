<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = array_merge($request->only('email', 'password'), ['is_active' => true]);

        if (!Auth::attempt($credentials)) {
            $userExists = \App\Models\User::where('email', $request->email)->first();

            if ($userExists && !$userExists->is_active) {
                return response()->json([
                    'message' => 'Tu cuenta ha sido desactivada.',
                ], 403);
            }

            return response()->json([
                'message' => 'Correo o contraseña incorrectos.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => new UserResource($user),
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ], 200);
    }
}