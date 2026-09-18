<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $size
 * @property string|null $logo_path
 * @property bool $is_configured
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'size',
        'logo_path',
        'is_configured',
    ];

    protected $casts = [
        'is_configured' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function directions(): HasMany
    {
        return $this->hasMany(Direction::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
