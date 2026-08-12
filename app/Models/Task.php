<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_manager_id',
        'title',
        'priority',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];

    public function caseManager()
    {
        return $this->belongsTo(User::class, 'case_manager_id');
    }
}