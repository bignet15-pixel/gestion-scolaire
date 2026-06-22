<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'nom',
        'prenom',
        'sexe',
        'email',
        'phone',
        'password',
        'role',
        'adresse',
        'matricule',
        'is_deleted',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_deleted' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function classesPrincipales()
    {
        return $this->hasMany(Classe::class, 'enseignant_principal_id');
    }

    public function affectations()
    {
        return $this->hasMany(ClasseMatiereUser::class);
    }

    public function evaluationsCreees()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function paiementsEnregistres()
    {
        return $this->hasMany(Paiement::class);
    }

    public function absencesRetardsEnregistres()
    {
        return $this->hasMany(AbsenceRetard::class, 'enregistre_par');
    }

    public function absencesRetardsStatutMisAJour()
    {
        return $this->hasMany(AbsenceRetard::class, 'statut_mis_a_jour_par');
    }

    public function sanctionsCreees()
    {
        return $this->hasMany(Sanction::class, 'created_by');
    }

    public function sanctionsAppliquees()
    {
        return $this->hasMany(SanctionAppliquee::class, 'applique_par');
    }

    public function decisionsSanctions()
    {
        return $this->hasMany(SanctionAppliquee::class, 'decision_par');
    }

    public function estGestionnaire(): bool
    {
        return $this->role === 'gestionnaire';
    }

    public function estEnseignant(): bool
    {
        return $this->role === 'enseignant';
    }

    public function enfants()
    {
        return $this->belongsToMany(Eleve::class, 'eleve_parent', 'parent_id', 'eleve_id')
            ->withPivot(['lien_parente', 'responsable_principal'])
            ->withTimestamps();
    }

    public function estParent(): bool
    {
        return $this->role === 'parent';
    } 

    public function justificationsAbsenceRetard()
    {
        return $this->hasMany(JustificationAbsenceRetard::class, 'parent_id');
    }

    public function paiementsDeclares()
    {
        return $this->hasMany(PaiementDeclare::class, 'parent_id');
    }

    public function demandesReinscription()
    {
        return $this->hasMany(DemandeReinscription::class, 'parent_id');
    }


}
