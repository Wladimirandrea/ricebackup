<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateScheduleDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'is_working' => ['required', 'boolean'],
            'start_time' => ['nullable', 'required_if:is_working,true', 'date_format:H:i'],
            'end_time'   => ['nullable', 'required_if:is_working,true', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        $lang = $this->header('Accept-Language', 'en');
        $isEs = str_contains($lang, 'es');

        return [
            'is_working.required'     => $isEs ? 'El estado es requerido.'                        : 'Status is required.',
            'start_time.required_if'  => $isEs ? 'La hora de inicio es requerida.'                : 'Start time is required.',
            'start_time.date_format'  => $isEs ? 'Formato de hora inválido (HH:MM).'              : 'Invalid time format (HH:MM).',
            'end_time.required_if'    => $isEs ? 'La hora de fin es requerida.'                   : 'End time is required.',
            'end_time.date_format'    => $isEs ? 'Formato de hora inválido (HH:MM).'              : 'Invalid time format (HH:MM).',
            'end_time.after'          => $isEs ? 'La hora de fin debe ser mayor a la de inicio.'  : 'End time must be after start time.',
        ];
    }
}
