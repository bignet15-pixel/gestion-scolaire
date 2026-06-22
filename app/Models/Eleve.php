<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Eleve extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'lieu_naissance',
        'contact_parent',
        'photo',
        'is_deleted',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'inscriptions', 'eleve_id', 'classe_id')
            ->withPivot([
                'id',
                'annee_scolaire_id',
                'date_inscription',
                'frais_attendu',
                'statut',
            ])
            ->withTimestamps();
    }

    public function notes()
    {
        return $this->hasManyThrough(
            Note::class,
            Inscription::class,
            'eleve_id',
            'inscription_id',
            'id',
            'id'
        );
    }

    public function nomComplet(): string
    {
        return $this->nom . ' ' . $this->prenom;
    }



    public function parents()
    {
        return $this->belongsToMany(User::class, 'eleve_parent', 'eleve_id', 'parent_id')
            ->withPivot(['lien_parente', 'responsable_principal'])
            ->withTimestamps();
    }
}
