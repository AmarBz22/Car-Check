<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'vehicle_id',
        'payment_id',
        'risk_score',
        'pdf_path',
        'generated_at',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function partner() {
    return $this->belongsTo(User::class, 'partner_id'); // partner who submitted
}

public function payments() {
    return $this->belongsToMany(Payment::class, 'payment_id');
}

    
}

