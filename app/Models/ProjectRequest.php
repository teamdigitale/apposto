<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectRequest extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'type',
        'status',
        'role',
        'message',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];
    
    protected $casts = [
        'reviewed_at' => 'datetime',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}