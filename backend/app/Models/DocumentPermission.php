<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $document_id
 * @property string $target_type   // 'poste' | 'service' | 'user'
 * @property int $target_id
 * @property Carbon|null $expires_at
 */
class DocumentPermission extends Model
{
    public const TARGET_POSTE = 'poste';
    public const TARGET_SERVICE = 'service';
    public const TARGET_USER = 'user';

    public const TARGETS = [
        self::TARGET_POSTE,
        self::TARGET_SERVICE,
        self::TARGET_USER,
    ];

    protected $fillable = [
        'document_id',
        'target_type',
        'target_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes de validité
    |--------------------------------------------------------------------------
    |
    | Une permission est valide si :
    |   expires_at IS NULL  -> permanente
    |   OR expires_at > maintenant
    |
    | Une permission expirée ne donne jamais accès.
    |
    */

    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    /**
     * Vrai si la permission est valide à l'instant t.
     */
    public function isValid(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function isPermanent(): bool
    {
        return $this->expires_at === null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
