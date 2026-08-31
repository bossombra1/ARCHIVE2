<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'company_id',
        'department_id',
        'name',
    ];

    // Relation avec l'entreprise
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Relation avec le département
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Relation avec les affectations (si un service est lié à des affectations d'employés)
    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }
}