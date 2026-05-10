<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendances';

    protected $fillable = [
        'session_id',
        'stagiaire_id',
        'type_absence_id',
        'justification',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public $timestamps = true;

    /**
     * Un enregistrement d'absence appartient a une session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Un enregistrement d'absence appartient a un stagiaire
     */
    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(Stagiaire::class);
    }

    /**
     * Un enregistrement d'absence appartient a un type d'absence
     */
    public function typeAbsence(): BelongsTo
    {
        return $this->belongsTo(TypeAbsence::class);
    }

    /**
     * Scope: obtenir les absences non justifiees
     */
    public function scopeUnjustified($query)
    {
        return $query->whereHas('typeAbsence', function ($q) {
            $q->where('code', 'ABSENT');
        })->whereNull('justification');
    }

    /**
     * Scope: obtenir les absences justifiees
     */
    public function scopeJustified($query)
    {
        return $query->whereNotNull('justification');
    }
}
