<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NiveauFormation extends Model
{
    protected $table = 'niveau_formations';

    protected $fillable = [
        'code',
        'nom',
    ];

    public $timestamps = true;

    /**
     * Un niveau a plusieurs programmes
     */
    public function programmes(): HasMany
    {
        return $this->hasMany(Programme::class, 'niveau_id');
    }
}
