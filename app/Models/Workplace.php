<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;

use Illuminate\Database\Eloquent\Model;

class Workplace extends Model
{
    //
    protected $fillable = ['name', 'address', 'num_place'];
 
    public function plans()
    {
        return $this->hasMany(Plan::class);
    }
}
