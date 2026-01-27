<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
   //


    protected $fillable = [ 'plan_id', 'is_exclusive', 'user_id', 'desk_id', 'from_date', 'to_date', 'start_date', 'end_date', 'start_time', 'end_time', 'status'];

    protected $casts = [
        'is_exclusive' => 'boolean',
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

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
