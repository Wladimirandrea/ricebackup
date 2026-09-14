<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'image_url', 'max_selection'];

    public function guests()
    {
        return $this->belongsToMany(Guest::class)->withTimestamps();
    }
}