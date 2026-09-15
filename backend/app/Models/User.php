<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $email
 * @property bool $status
 * @property string $lang
 * @property string $theme_color
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'status',
        'lang',
        'theme_color',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'boolean',
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

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    /**
     * Grant de droits d'action (modifier/supprimer/ajouter) accordé par
     * l'Administrateur Système à cet utilisateur. Une seule ligne possible
     * (contrainte unique company_id+user_id) — cf. DocumentActionGrant.
     */
    public function documentActionGrant(): HasOne
    {
        return $this->hasOne(DocumentActionGrant::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Affectation active (single source of truth)
    |--------------------------------------------------------------------------
    |
    | Une affectation est "active" si :
    |   is_active = true
    |   AND started_at <= aujourd'hui
    |   AND (ended_at IS NULL OR ended_at >= aujourd'hui)
    |
    | Si l'utilisateur a plusieurs affectations actives simultanément,
    | on lève une AmbiguousAffectationException côté applicatif plutôt que
    | de choisir arbitrairement avec ->first().
    |
    | La résolution est mise en cache sur l'instance du modèle pour la durée
    | de la requête HTTP (évite de relire la base plusieurs fois dans un même
    | cycle policy -> controller -> service).
    |
    */

    public function activeAffectation(): ?Affectation
    {
        // Cache par instance : une seule requête par cycle HTTP.
        if (array_key_exists('__activeAffectation', $this->attributes)) {
            return $this->attributes['__activeAffectation'];
        }

        $active = $this->affectations()
            ->active()
            ->with(['poste:id,name,level', 'service:id,company_id,department_id,name', 'department:id,company_id,direction_id,name', 'direction:id,company_id,name'])
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();

        if ($active->isEmpty()) {
            return $this->cacheActiveAffectation(null);
        }

        if ($active->count() > 1) {
            throw new AmbiguousAffectationException($this->id, $active->count());
        }

        return $this->cacheActiveAffectation($active->first());
    }

    private function cacheActiveAffectation(?Affectation $affectation): ?Affectation
    {
        $this->attributes['__activeAffectation'] = $affectation;

        return $affectation;
    }

    public function hasActiveAffectation(): bool
    {
        try {
            return $this->activeAffectation() !== null;
        } catch (AmbiguousAffectationException) {
            return false;
        }
    }

    /**
     * Retourne le poste.level de l'affectation active, ou null.
     * À utiliser pour toute décision de sécurité basée sur le poste.
     */
    public function currentPosteLevel(): ?string
    {
        return $this->activeAffectation()?->poste?->level;
    }

    public function currentPosteId(): ?int
    {
        return $this->activeAffectation()?->poste_id;
    }

    public function currentServiceId(): ?int
    {
        return $this->activeAffectation()?->service_id;
    }

    public function currentDepartmentId(): ?int
    {
        $aff = $this->activeAffectation();
        if (! $aff) {
            return null;
        }
        // L'affectation peut porter department_id directement ;
        // sinon on remonte via le service.
        return $aff->department_id ?? $aff->service?->department_id;
    }

    public function currentDirectionId(): ?int
    {
        $aff = $this->activeAffectation();
        if (! $aff) {
            return null;
        }
        if ($aff->direction_id) {
            return $aff->direction_id;
        }
        if ($aff->department_id && $aff->department) {
            return $aff->department->direction_id;
        }
        return $aff->service?->department?->direction_id;
    }

    public function currentCompanyId(): ?int
    {
        return $this->company_id;
    }
}
