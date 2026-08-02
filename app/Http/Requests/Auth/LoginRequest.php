<?php
// app/Http/Requests/Auth/LoginRequest.php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        $locale = $this->header('Accept-Language', 'es');
        $isEnglish = str_starts_with($locale, 'en');

        if ($isEnglish) {
            return [
                'email.required'    => 'The email address is required.',
                'email.email'       => 'Please enter a valid email address.',
                'password.required' => 'The password is required.',
                'password.min'      => 'The password must be at least 8 characters.',
            ];
        }

        return [
            'email.required'    => 'El correo electrónico es requerido.',
            'email.email'       => 'Por favor ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es requerida.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    /** Devuelve JSON en lugar de redirigir */
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
