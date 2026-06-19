<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sanction extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = ['absence', 'retard', 'conduite'];

    public const MODES_DECLENCHEMENT = ['automatique', 'manuel', 'mixte'];

    public const STATUTS_DECLENCHEURS = ['tous', 'en_attente', 'non_justifiee', 'refusee'];

    public const PERIODES_CALCUL = ['semaine', 'mois', 'trimestre', 'annee'];

    public const NIVEAUX_GRAVITE = ['faible', 'moyen', 'grave'];

    public const TYPES_EFFET = [
        'appel_parent',
        'convocation_administration',
        'points_en_moins',
        'avertissement',
        'autre',
    ];

    protected $fillable = [
        'nom',
        'description',
        'categorie',
        'mode_declenchement',
        'statut_declencheur',
        'seuil',
        'periode_calcul',
        'niveau_gravite',
        'type_effet',
        'valeur_effet',
        'active',
        'visible_parent_defaut',
        'created_by',
    ];

    protected $casts = [
        'valeur_effet' => 'decimal:2',
        'active' => 'boolean',
        'visible_parent_defaut' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function sanctionsAppliquees()
    {
        return $this->hasMany(SanctionAppliquee::class);
    }
}
