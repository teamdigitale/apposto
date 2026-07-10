<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presence extends Model
{
    protected $fillable = ['user_id', 'booking_id', 'date', 'status', 'workplace_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function workplace()
    {
        return $this->belongsTo(Workplace::class);
    }

    public function getResolvedWorkplaceAttribute()
    {
        return $this->workplace
            ?? $this->booking?->desk?->plan?->workplace;
    }
}