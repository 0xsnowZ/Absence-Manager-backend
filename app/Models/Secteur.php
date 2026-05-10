<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Secteur extends Model
{
    protected $table = 'secteurs';

    protected $fillable = [
        'code',
        'nom',
    ];

    public $timestamps = true;

    /**
     * Une secteur a plusieurs filieres
     */
    public function filieres(): HasMany
    {
        return $this->hasMany(Filiere::class);
    }
}
