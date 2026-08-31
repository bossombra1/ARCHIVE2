<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    // Autoriser l'assignation de masse pour ces colonnes
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    // Relation avec l'utilisateur (si elle n'y est pas déjà)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
