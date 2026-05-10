<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Programme extends Model
{
    protected $table = 'programmes';

    protected $fillable = [
        'code_diplome',
        'libelle_long',
        'filiere_id',
        'niveau_id',
        'annee',
        'saison',
        'is_cds',
    ];

    protected $casts = [
        'is_cds' => 'boolean',
        'annee' => 'integer',
        'saison' => 'integer',
    ];

    public $timestamps = true;

    /**
     * Un programme appartient a une filiere
     */
    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    /**
     * Un programme appartient a un niveau
     */
    public function niveau(): BelongsTo
    {
        return $this->belongsTo(NiveauFormation::class, 'niveau_id');
    }

    /**
     * Un programme a plusieurs sessions
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Un programme a plusieurs inscriptions
     */
    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    /**
     * Un programme a plusieurs stagiaires (via inscriptions)
     */
    public function stagiaires(): BelongsToMany
    {
        return $this->belongsToMany(Stagiaire::class, 'inscriptions');
    }

    /**
     * Scope: obtenir les programmes d'une saison
     */
    public function scopeBySaison($query, $saison)
    {
        return $query->where('saison', $saison);
    }

    /**
     * Scope: obtenir les programmes par code diplome
     */
    public function scopeByCodeDiplome($query, $codeDiplome)
    {
        return $query->where('code_diplome', $codeDiplome);
    }
}
