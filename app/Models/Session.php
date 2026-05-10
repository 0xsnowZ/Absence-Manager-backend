<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    protected $table = 'sessions';

    protected $fillable = [
        'programme_id',
        'date_session',
        'heure_debut',
        'heure_fin',
        'lieu',
        'created_by',
    ];

    protected $casts = [
        'date_session' => 'date',
    ];

    public $timestamps = true;

    /**
     * Une session appartient a un programme
     */
    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    /**
     * Une session a plusieurs enregistrements d'absences
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Scope: obtenir les sessions futures
     */
    public function scopeFuture($query)
    {
        return $query->where('date_session', '>=', now()->toDateString());
    }

    /**
     * Scope: obtenir les sessions passees
     */
    public function scopePast($query)
    {
        return $query->where('date_session', '<', now()->toDateString());
    }
}
