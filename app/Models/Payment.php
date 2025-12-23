<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_id',          // better than chassis for FK integrity
        'amount',
        'currency',
        'chargily_payment_id',
        'status',
    ];

    /**
     * The user who made the payment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The vehicle this payment is for
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Optional: reports associated with this payment
     */
    public function reports()
    {
        return $this->belongsToMany(Report::class, 'payment_report');
    }
}
