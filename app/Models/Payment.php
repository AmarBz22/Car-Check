<?php
// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'amount',
        'currency',
        'chargily_payment_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'amount'     => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The report unlocked by this payment
     */
    public function report()
    {
        return $this->hasOne(Report::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function hasActiveAccess(): bool
    {
        return $this->status === 'paid'
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    /**
     * Scope: find a valid paid + non-expired payment for user + vehicle
     */
    public function scopeActiveAccessFor($query, int $userId, int $vehicleId)
    {
        return $query
            ->where('user_id', $userId)
            ->where('vehicle_id', $vehicleId)
            ->where('status', 'paid')
            ->where('expires_at', '>', now());
    }
}
