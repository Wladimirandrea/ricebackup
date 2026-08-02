<?php
// app/Http/Requests/CaseManager/ListCaseManagersRequest.php

namespace App\Http\Requests\CaseManager;

use Illuminate\Foundation\Http\FormRequest;

class ListCaseManagersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:4', 'max:48'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        $lang = $this->header('Accept-Language', 'en');

        $messages = [
            'es' => [
                'search.max'      => 'La búsqueda no puede superar 100 caracteres.',
                'per_page.integer'=> 'El campo per_page debe ser un número entero.',
                'per_page.min'    => 'El mínimo de resultados por página es 4.',
                'per_page.max'    => 'El máximo de resultados por página es 48.',
            ],
            'en' => [
                'search.max'      => 'Search cannot exceed 100 characters.',
                'per_page.integer'=> 'The per_page field must be an integer.',
                'per_page.min'    => 'Minimum results per page is 4.',
                'per_page.max'    => 'Maximum results per page is 48.',
            ],
        ];

        return $messages[str_contains($lang, 'es') ? 'es' : 'en'];
    }
}