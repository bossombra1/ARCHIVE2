<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande de changement de forfait (cf. migration
 * create_plan_change_requests_table).
 *
 * L'application ne peut pas valider une demande elle-même : le passage
 * pending -> approved/rejected est effectué MANUELLEMENT hors application
 * (en base ou via `php artisan plan:process`). L'application peut seulement
 * créer la demande (admin) puis l'appliquer si elle a été approuvée.
 *
 * @property int $id
 * @property int $company_id
 * @property int $requested_by
 * @property string $current_size
 * @property string $requested_size
 * @property string $status  pending|approved|rejected|applied
 * @property int|null $decided_by
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property string|null $note
 */
class PlanChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_APPLIED = 'applied';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_APPLIED,
    ];

    protected $fillable = [
        'company_id',
        'requested_by',
        'current_size',
        'requested_size',
        'status',
        'decided_by',
        'decided_at',
        'note',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
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

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes & helpers
    |--------------------------------------------------------------------------
    */

    /** Demandes encore "actives" (en attente OU approuvée non appliquée). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Dernière demande non clôturée de l'entreprise (pending ou approved),
     * ou null si aucune : sert de garde-fou avant toute nouvelle demande /
     * application. Une demande refusée n'est PAS "ouverte" : elle ne bloque
     * plus rien.
     */
    public static function openFor(Company $company): ?self
    {
        return self::open()
            ->where('company_id', $company->id)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Dernière demande visible pour l'affichage (tout statut sauf applied),
     * ou null : permet notamment à l'admin de voir qu'une demande a été
     * refusée hors application, tout en pouvant en re-soumettre une.
     */
    public static function latestVisibleFor(Company $company): ?self
    {
        return self::where('company_id', $company->id)
            ->where('status', '!=', self::STATUS_APPLIED)
            ->orderByDesc('id')
            ->first();
    }
}
