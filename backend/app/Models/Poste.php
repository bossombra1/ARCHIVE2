<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $level  // 'admin' | 'dg' | 'directeur' | 'responsable_departement' | 'chef_service' | 'employe' | 'agent_temporaire'
 */
class Poste extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
    ];

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }
}
