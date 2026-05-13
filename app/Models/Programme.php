<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Programme extends Model
{
    protected $table = 'classes';

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
        'annee'  => 'integer',
        'saison' => 'integer',
    ];

    public $timestamps = true;

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(NiveauFormation::class, 'niveau_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'classe_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'classe_id');
    }

    public function stagiaires(): BelongsToMany
    {
        return $this->belongsToMany(Stagiaire::class, 'inscriptions', 'classe_id', 'stagiaire_id');
    }

    public function scopeBySaison($query, $saison)
    {
        return $query->where('saison', $saison);
    }

    public function scopeByCodeDiplome($query, $codeDiplome)
    {
        return $query->where('code_diplome', $codeDiplome);
    }
}
