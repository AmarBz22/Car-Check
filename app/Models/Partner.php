<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Partner extends Authenticatable
{
    protected $table = 'users'; // same table as User

    // Only include users with role = 'partner'
    protected static function booted()
    {
        static::addGlobalScope('partner', function (Builder $builder) {
            $builder->where('role', 'partner');
        });
    }

    // Fillable fields for creating a partner
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // will always be 'partner'
    ];

    protected $hidden = [
        'password',
    ];

    // Relationships
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'verified_by');
    }

    // Helper method
    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }



    public function reports() {
    return $this->hasMany(Report::class, 'partner_id');
}
}
