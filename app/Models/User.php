<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'team_id',
        'priority',
        'allow_view',
        'phone',
        'default_workstation_id',
        'ferie_totali',
        'ferie_usate',
        'gestiamopresenze',
        'giorni_in_smart',
        'superuser',
        'addetto_emergenza',
        'addetto_al_primo_soccorso',
        'ruolo'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function bookings_active()
    {
        $today = Carbon::today();
        $prenotazione_ultima = $this->hasMany(Booking::class)
           ->where('status','=','0')
            ->where('from_date', '<=', $today)
            ->where('to_date', '>=', $today);
        
        return $prenotazione_ultima->with('desk');
        
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function getEmailAttribute($value)
    {
        //print($value);
        return strtolower($value);
    }
    
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    public function defaultWorkstation()
    {
        return $this->belongsTo(Desk::class, 'default_workstation_id');
    }

    public function presences()
    {
        return $this->hasMany(Presence::class);
    }

 
}
