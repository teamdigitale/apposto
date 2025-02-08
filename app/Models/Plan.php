<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    //
    protected $fillable = ['description', 'workplace_id', 'cover_image','user_name'];

    public function workplace()
    {
        return $this->belongsTo(Workplace::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'plans_teams', 'plan_id','team_id');
    }
    
    public function desks()
    {
        return $this->hasMany(Desk::class)->where('active', 1);
    }
}
