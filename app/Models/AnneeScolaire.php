<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnneeScolaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'libelle',
        'date_debut',
        'date_fin',
        'statut',
        'is_deleted',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function trimestres()
    {
        return $this->hasMany(Trimestre::class);
    }

    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function estActive(): bool
    {
        return $this->statut === 'active';
    }

    public function estFermee(): bool
    {
        return $this->statut === 'fermee';
    }
}