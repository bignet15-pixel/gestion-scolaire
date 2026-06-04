<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'annee_scolaire_id',
        'date_inscription',
        'frais_attendu',
        'statut',
        'is_deleted',
    ];

    protected $casts = [
        'date_inscription' => 'date',
        'frais_attendu' => 'decimal:2',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function totalPaye(): float
    {
        return (float) $this->paiements()->sum('montant');
    }

    public function resteAPayer(): float
    {
        return (float) $this->frais_attendu - $this->totalPaye();
    }

    public function estSoldee(): bool
    {
        return $this->resteAPayer() <= 0;
    }

    public function estActif(): bool
    {
        return $this->statut === 'actif';
    }
}