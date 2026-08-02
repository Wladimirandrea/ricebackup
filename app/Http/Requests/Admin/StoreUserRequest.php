<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'role'          => ['required', 'in:admin,case_manager,client'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        $isEnglish = str_starts_with($this->header('Accept-Language', 'es'), 'en');

        if ($isEnglish) {
            return [
                'name.required'            => 'Name is required.',
                'email.required'           => 'Email is required.',
                'email.unique'             => 'This email is already registered.',
                'password.required'        => 'Password is required.',
                'password.min'             => 'Password must be at least 8 characters.',
                'password.confirmed'       => 'Passwords do not match.',
                'role.required'            => 'Role is required.',
                'role.in'                  => 'Invalid role.',
                'profile_image.image'      => 'File must be an image.',
                'profile_image.max'        => 'Image must not exceed 2MB.',
                'case_manager_id.required_if' => 'A Case Manager must be assigned to the client.',
                'case_manager_id.exists'   => 'Selected Case Manager does not exist.',
            ];
        }

        return [
            'name.required'            => 'El nombre es requerido.',
            'email.required'           => 'El correo es requerido.',
            'email.unique'             => 'Este correo ya está registrado.',
            'password.required'        => 'La contraseña es requerida.',
            'password.min'             => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'       => 'Las contraseñas no coinciden.',
            'role.required'            => 'El rol es requerido.',
            'role.in'                  => 'Rol inválido.',
            'profile_image.image'      => 'El archivo debe ser una imagen.',
            'profile_image.max'        => 'La imagen no debe superar 2MB.',
            'case_manager_id.required_if' => 'Debes asignar un Case Manager al cliente.',
            'case_manager_id.exists'   => 'El Case Manager seleccionado no existe.',
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
