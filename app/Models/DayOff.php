<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayOff extends Model
{
    protected $table = 'days_off';

    protected $fillable = ['date', 'start_time', 'end_time', 'reason'];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    
}
