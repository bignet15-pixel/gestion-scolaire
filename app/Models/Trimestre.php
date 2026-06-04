<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trimestre extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'annee_scolaire_id',
        'nom',
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

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function estActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function estFerme(): bool
    {
        return $this->statut === 'ferme';
    }
}