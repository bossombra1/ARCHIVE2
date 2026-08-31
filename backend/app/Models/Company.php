<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    /**
     * Les attributs qui ne sont pas protégés contre l'affectation en masse.
     */
    protected $guarded = [];
}