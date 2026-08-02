<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
   protected $fillable = [
        'day_of_week',
        'is_working',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'is_working' => 'boolean',
        ];
    }

    public static function dayName(int $day, string $locale = 'en'): string
    {
        $days = [
            'en' => ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
            'es' => ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],
        ];

        return $days[$locale][$day] ?? '';
    }
}
