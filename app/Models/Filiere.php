<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    protected $table = 'filieres';

    protected $fillable = [
        'code',
        'nom',
        'secteur_id',
    ];

    public $timestamps = true;

    /**
     * Une filiere appartient a un secteur
     */
    public function secteur(): BelongsTo
    {
        return $this->belongsTo(Secteur::class);
    }

    /**
     * Une filiere a plusieurs programmes
     */
    public function programmes(): HasMany
    {
        return $this->hasMany(Programme::class, 'filiere_id');
    }
}
