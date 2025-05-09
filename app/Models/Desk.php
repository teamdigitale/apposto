<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Desk extends Model
{
    //
    protected $fillable = ['identifier', 'plan_id',  'active'];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'default_workstation_id');
    }
}
