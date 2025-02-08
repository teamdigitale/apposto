<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
   //


    protected $fillable = ['user_id', 'desk_id', 'from_date', 'to_date', 'start_date', 'end_date', 'start_time', 'end_time', 'status'];

    public function desk()
    {
        return $this->belongsTo(Desk::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
