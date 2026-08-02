<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        $isEnglish = str_starts_with($this->header('Accept-Language', 'es'), 'en');

        if ($isEnglish) {
            return [
                'email.required' => 'The email address is required.',
                'email.email'    => 'Please enter a valid email address.',
                'email.exists'   => 'No account found with this email address.',
            ];
        }

        return [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email'    => 'Por favor ingresa un correo válido.',
            'email.exists'   => 'No encontramos una cuenta con este correo.',
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