<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        $isEnglish = str_starts_with($this->header('Accept-Language', 'es'), 'en');

        if ($isEnglish) {
            return [
                'token.required'             => 'Reset token is required.',
                'email.required'             => 'The email address is required.',
                'email.exists'               => 'No account found with this email.',
                'password.required'          => 'The password is required.',
                'password.min'               => 'Password must be at least 8 characters.',
                'password.confirmed'         => 'Passwords do not match.',
            ];
        }

        return [
            'token.required'             => 'El token de recuperación es requerido.',
            'email.required'             => 'El correo electrónico es requerido.',
            'email.exists'               => 'No encontramos una cuenta con este correo.',
            'password.required'          => 'La contraseña es requerida.',
            'password.min'               => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'         => 'Las contraseñas no coinciden.',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Error de validación.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}