<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- Indispensable pour Sanctum

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
        'theme_color'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Notez bien 'hashed' avec un 'd' (recommandé en Laravel)
        'status' => 'boolean',
    ];

    // Relation avec l'entreprise
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Relation avec les affectations (historique et poste actuel)
    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }
}