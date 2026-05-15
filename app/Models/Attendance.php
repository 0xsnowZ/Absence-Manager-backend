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
        'status',
        'created_by_user_id',
        'updated_by_user_id',
        'justification',
        'justified_at',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'justified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
     * L'utilisateur qui a créé l'absence
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * L'utilisateur qui a mis à jour l'absence en dernier
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * The "booting" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically set created_by_user_id on create
        static::creating(function ($model) {
            if (auth()->check() && !$model->created_by_user_id) {
                $model->created_by_user_id = auth()->id();
            }
        });

        // Automatically set updated_by_user_id on update
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by_user_id = auth()->id();
            }
        });
    }

    /**
     * Scope: obtenir les absences non justifiees
     */
    public function scopeUnjustified($query)
    {
        return $query->where('status', 'non_justifie');
    }

    /**
     * Scope: obtenir les absences justifiees
     */
    public function scopeJustified($query)
    {
        return $query->where('status', 'justifie');
    }

    /**
     * Scope: obtenir les absences avec retard
     */
    public function scopeLate($query)
    {
        return $query->where('status', 'retard');
    }

    /**
     * Scope: obtenir les absences excusées
     */
    public function scopeExcused($query)
    {
        return $query->where('status', 'absence_excusee');
    }
}
