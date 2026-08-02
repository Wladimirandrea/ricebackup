<?php

namespace App\Http\Requests;


use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DayOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'       => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'reason'     => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        $locale = str_starts_with($this->header('Accept-Language', 'en'), 'es') ? 'es' : 'en';

        return [
            'en' => [
                'date.required'       => 'The date is required.',
                'date.date_format'    => 'Date must be YYYY-MM-DD format.',
                'start_time.required' => 'Start time is required.',
                'start_time.date_format' => 'Start time must be HH:MM format.',
                'end_time.required'   => 'End time is required.',
                'end_time.date_format' => 'End time must be HH:MM format.',
                'end_time.after'      => 'End time must be after start time.',
            ],
            'es' => [
                'date.required'       => 'La fecha es obligatoria.',
                'date.date_format'    => 'La fecha debe tener formato AAAA-MM-DD.',
                'start_time.required' => 'La hora de inicio es obligatoria.',
                'start_time.date_format' => 'La hora de inicio debe tener formato HH:MM.',
                'end_time.required'   => 'La hora de fin es obligatoria.',
                'end_time.date_format' => 'La hora de fin debe tener formato HH:MM.',
                'end_time.after'      => 'La hora de fin debe ser posterior a la hora de inicio.',
            ],
        ][$locale];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422)
        );
    }
}
