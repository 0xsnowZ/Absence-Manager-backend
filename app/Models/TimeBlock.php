<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeBlock extends Model
{
    protected $table = 'time_blocks';

    protected $fillable = [
        'code',
        'label',
        'heure_debut',
        'heure_fin',
    ];

    protected $casts = [
        'heure_debut' => 'datetime:H:i:s',
        'heure_fin' => 'datetime:H:i:s',
    ];

    public $timestamps = true;

    /**
     * A time block has many sessions
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}
