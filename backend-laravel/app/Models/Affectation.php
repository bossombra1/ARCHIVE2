<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affectation extends Model
{
    protected $fillable = [
        'user_id',
        'poste_id',
        'service_id',
        'department_id',
        'direction_id',
        'is_active',
        'started_at',
        'ended_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function poste()
    {
        return $this->belongsTo(Poste::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function direction()
    {
        return $this->belongsTo(Direction::class);
    }
}