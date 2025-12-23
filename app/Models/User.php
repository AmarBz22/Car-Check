<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Hidden attributes when returning JSON
     */
    protected $hidden = [
        'password',
       
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /* =========================
       Role helpers
       ========================= */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSource(): bool
    {
        return $this->role === 'source';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }
      public function isVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }
    
    /* =========================
       Relationships (later)
       ========================= */

       public function payments() {
    return $this->hasMany(Payment::class);
}

    // public function purchases() {}
    // public function reports() {}
}
