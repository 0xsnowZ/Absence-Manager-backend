<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscription extends Model
{
    protected $table = 'inscriptions';

    protected $fillable = [
        'stagiaire_id',
        'programme_id',
        'date_inscription',
        'date_dossier_complet',
    ];

    protected $casts = [
        'date_inscription' => 'date',
        'date_dossier_complet' => 'date',
    ];

    public $timestamps = true;

    /**
     * Une inscription appartient a un stagiaire
     */
    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(Stagiaire::class);
    }

    /**
     * Une inscription appartient a un programme
     */
    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }
}
