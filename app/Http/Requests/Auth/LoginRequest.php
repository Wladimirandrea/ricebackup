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
        $isEnglish = str_starts_with($this->header('Accept-Language', 'es'), 'en');

        return [
            'email.required'    => $isEnglish ? 'The email address is required.' : 'El correo electrónico es requerido.',
            'email.email'       => $isEnglish ? 'Please enter a valid email address.' : 'Por favor ingresa un correo electrónico válido.',
            'password.required' => $isEnglish ? 'The password is required.' : 'La contraseña es requerida.',
            'password.min'      => $isEnglish ? 'The password must be at least 8 characters.' : 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
