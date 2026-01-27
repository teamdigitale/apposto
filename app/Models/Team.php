<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    //
    protected $fillable = ['label', 'allow_multi_day', 'can_book_exclusive'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plans_teams', 'team_id', 'plan_id');
    }

}
