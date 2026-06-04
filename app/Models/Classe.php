<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'annee_scolaire_id',
        'enseignant_principal_id',
        'chef_classe_id',
        'niveau',
        'nom',
        'frais_scolarite',
        'is_deleted',
    ];

    protected $casts = [
        'frais_scolarite' => 'decimal:2',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function enseignantPrincipal()
    {
        return $this->belongsTo(User::class, 'enseignant_principal_id');
    }

    public function chefClasse()
    {
        return $this->belongsTo(Eleve::class, 'chef_classe_id');
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function affectations()
    {
        return $this->hasMany(ClasseMatiereUser::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function eleves()
    {
        return $this->belongsToMany(Eleve::class, 'inscriptions', 'classe_id', 'eleve_id')
            ->withPivot([
                'id',
                'annee_scolaire_id',
                'date_inscription',
                'frais_attendu',
                'statut',
            ])
            ->withTimestamps();
    }
}