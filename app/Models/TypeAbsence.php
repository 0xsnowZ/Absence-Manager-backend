<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeAbsence extends Model
{
    protected $table = 'type_absences';

    protected $fillable = [
        'code',
        'libelle',
    ];

    public $timestamps = true;

    /**
     * Un type d'absence a plusieurs enregistrements d'absences
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
