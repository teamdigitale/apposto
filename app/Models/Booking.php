<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'is_exclusive',
        'user_id',
        'desk_id',
        'from_date',
        'to_date',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'is_exclusive' => 'boolean',
        'from_date' => 'datetime',
        'to_date' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function desk()
    {
        return $this->belongsTo(Desk::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function presence()
    {
        return $this->hasOne(Presence::class);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::Confirmed);
    }

    public function statusEnum(): ?BookingStatus
    {
        return BookingStatus::tryFrom((int) $this->status);
    }
}
