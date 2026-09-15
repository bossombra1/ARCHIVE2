<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id       // bénéficiaire du grant
 * @property int $granted_by    // Administrateur Système qui a accordé le droit
 * @property bool $can_modify
 * @property bool $can_delete
 * @property bool $can_add
 * @property string $scope      // 'all' | 'specific'
 * @property Carbon|null $expires_at
 */
class DocumentActionGrant extends Model
{
    public const SCOPE_ALL = 'all';
    public const SCOPE_SPECIFIC = 'specific';

    public const SCOPES = [
        self::SCOPE_ALL,
        self::SCOPE_SPECIFIC,
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'granted_by',
        'can_modify',
        'can_delete',
        'can_add',
        'scope',
        'expires_at',
    ];

    protected $casts = [
        'can_modify' => 'boolean',
        'can_delete' => 'boolean',
        'can_add' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Documents ciblés quand scope = 'specific'. Non pertinent (ignoré) si
     * scope = 'all'.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            Document::class,
            'document_action_grant_targets',
            'grant_id',
            'document_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes de validité (même logique que DocumentPermission)
    |--------------------------------------------------------------------------
    */

    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

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

    public function isAllScope(): bool
    {
        return $this->scope === self::SCOPE_ALL;
    }

    /**
     * Vrai si ce grant couvre le document donné (portée 'all', ou document
     * listé en portée 'specific'). Ne vérifie PAS can_modify/can_delete
     * eux-mêmes ni la validité temporelle : à combiner avec isValid().
     */
    public function coversDocument(int $documentId): bool
    {
        if ($this->isAllScope()) {
            return true;
        }
        return $this->documents()->where('documents.id', $documentId)->exists();
    }
}