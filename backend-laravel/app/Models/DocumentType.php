<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $table = 'document_types';

    protected $fillable = [
        'company_id',
        'name',
    ];

    // Relation avec l'entreprise
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Relation avec les documents liés à ce type
    public function documents()
    {
        return $this->hasMany(Document::class, 'document_type_id');
    }
}