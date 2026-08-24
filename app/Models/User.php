<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\BookingStatus;
use App\Notifications\ResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
        'is_project_manager',
        'addetto_emergenza',
        'addetto_al_primo_soccorso',
        'ruolo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'superuser' => 'boolean',
            'is_project_manager' => 'boolean',
            'allow_view' => 'boolean',
        ];
    }

    public function hasFullFrontendVisibility(): bool
    {
        return $this->superuser || $this->is_project_manager;
    }

    public function canManageProjects(): bool
    {
        return $this->hasFullFrontendVisibility();
    }

    public function managesProject(Project $project): bool
    {
        if ($this->superuser) {
            return true;
        }

        if (! $this->is_project_manager) {
            return false;
        }

        return $this->projects()
            ->wherePivot('role', 'manager')
            ->where('projects.id', $project->id)
            ->exists();
    }

    public function visibleProjects()
    {
        if ($this->hasFullFrontendVisibility()) {
            return Project::query()->where('active', true);
        }

        return $this->projects()->where('active', true);
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function bookings_active()
    {
        $today = Carbon::today();

        return $this->hasMany(Booking::class)
            ->where('status', BookingStatus::Confirmed)
            ->where('from_date', '<=', $today)
            ->where('to_date', '>=', $today)
            ->with('desk');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function getEmailAttribute($value)
    {
        return strtolower($value);
    }

    public function defaultWorkstation()
    {
        return $this->belongsTo(Desk::class, 'default_workstation_id');
    }

    public function presences()
    {
        return $this->hasMany(Presence::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function getFerieUsateAttribute()
    {
        return $this->presences()
            ->where('status', 'ferie')
            ->count();
    }

    public function getRemainingLeaveDaysAttribute()
    {
        return $this->ferie_totali - $this->ferie_usate;
    }
}
