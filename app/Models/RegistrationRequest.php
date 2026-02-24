<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'company_name',
        'phone',
        'reason',
        'status', // pending, approved, rejected
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Mark as approved
     */
    public function approve()
    {
        $this->update(['status' => 'approved']);
    }

    /**
     * Mark as rejected
     */
    public function reject()
    {
        $this->update(['status', 'rejected']);
    }
}
