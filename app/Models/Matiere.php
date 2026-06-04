<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Matiere extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'coefficient_default',
        'is_deleted',
    ];

    protected $casts = [
        'coefficient_default' => 'integer',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function affectations()
    {
        return $this->hasMany(ClasseMatiereUser::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }
}