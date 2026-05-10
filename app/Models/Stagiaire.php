<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Stagiaire extends Model
{
    protected $table = 'stagiaires';

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'cin',
        'telephone',
    ];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    public $timestamps = true;

    /**
     * Un stagiaire a plusieurs inscriptions
     */
    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    /**
     * Un stagiaire est inscrit dans plusieurs programmes
     */
    public function programmes(): BelongsToMany
    {
        return $this->belongsToMany(Programme::class, 'inscriptions');
    }

    /**
     * Un stagiaire a plusieurs enregistrements d'absences
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Scope: obtenir les stagiaires d'un programme
     */
    public function scopeInProgramme($query, $programmeId)
    {
        return $query->whereHas('inscriptions', function ($q) use ($programmeId) {
            $q->where('programme_id', $programmeId);
        });
    }

    /**
     * Scope: obtenir les stagiaires d'une saison
     */
    public function scopeInSaison($query, $saison)
    {
        return $query->whereHas('programmes', function ($q) use ($saison) {
            $q->where('saison', $saison);
        });
    }
}
