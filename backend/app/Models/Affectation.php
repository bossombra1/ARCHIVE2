<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $poste_id
 * @property int $service_id
 * @property int|null $department_id
 * @property int|null $direction_id
 * @property bool $is_active
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 */
class Affectation extends Model
{
    protected $fillable = [
        'user_id',
        'poste_id',
        'service_id',
        'department_id',
        'direction_id',
        'is_active',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope "active"
    |--------------------------------------------------------------------------
    |
    | Une affectation est active si :
    |   is_active = true
    |   AND started_at <= aujourd'hui
    |   AND (ended_at IS NULL OR ended_at >= aujourd'hui)
    |
    | La comparaison des dates utilise la date courante du serveur (sans
    | heure), ce qui correspond à la sémantique "started_at / ended_at sont
    | des dates calendrier". La timezone est celle configurée par l'app
    | (config('app.timezone')).
    |
    */

    public function scopeActive(Builder $query): Builder
    {
        $today = today();

        return $query->where('is_active', true)
            ->where('started_at', '<=', $today)
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('ended_at')->orWhere('ended_at', '>=', $today);
            });
    }

    /**
     * Scope pour vérifier qu'une affectation est active pour un user précis.
     * Usage : Affectation::forUser($userId)->active()->get()
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
