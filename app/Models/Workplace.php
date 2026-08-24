<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workplace extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'num_place'];

    public function plans()
    {
        return $this->hasMany(Plan::class);
    }
}
