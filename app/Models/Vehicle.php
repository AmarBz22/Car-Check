<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'chassis',
        'brand',
        'model',
        'year',
        'engine',
        'user_id',
    ];

    // Relationship: Vehicle belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
