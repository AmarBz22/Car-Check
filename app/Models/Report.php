<?php
// app/Models/Report.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'vehicle_id',
        'payment_id',
        'partner_id',
        'report_type',
        'findings',
        'kilometrage',
        'risk_score',
        'pdf_path',
        'status',
        'generated_at',
        'report_date',
    ];

    protected $casts = [
        'findings'     => 'json',
        'report_date'  => 'datetime',
        'generated_at' => 'datetime',
    ];

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // ─── Relationships ────────────────────────────────────────────

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Is this report downloadable right now?
     * Checks the linked payment's 48h window.
     */
    public function isAccessibleBy(int $userId): bool
    {
        return $this->payment !== null
            && $this->payment->user_id === $userId
            && $this->payment->hasActiveAccess();
    }
}
