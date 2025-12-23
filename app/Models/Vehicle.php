<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
    'plate_number', // if you include this
    'vin',
    'brand',
    'model',
    'year',
    'color',
    'status',
    'verified_by',
];

    // Relationship: Vehicle belongs to a User
    public function verifier()
{
    return $this->belongsTo(User::class, 'verified_by');
}
public function reports()
{
    return $this->hasMany(Report::class);
}

public function payments() {
    return $this->hasMany(Payment::class);


}
}