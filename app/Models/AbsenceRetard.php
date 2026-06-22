<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AbsenceRetard extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'absences_retards';

    public const TYPES = ['absence', 'retard'];

    public const PERIODES = ['journee', 'matin', 'apres_midi', 'cours'];

    public const CATEGORIES_MOTIF = [
        'maladie',
        'familial',
        'transport',
        'administratif',
        'discipline',
        'non_renseigne',
        'autre',
    ];

    public const STATUTS = ['en_attente', 'justifiee', 'non_justifiee', 'refusee'];

    public const SOURCES = ['enseignant', 'gestionnaire', 'parent', 'surveillance', 'autre'];

    protected $fillable = [
        'inscription_id',
        'type',
        'date_debut',
        'date_fin',
        'periode',
        'heure_debut',
        'heure_fin',
        'heure_arrivee',
        'duree_minutes',
        'categorie_motif',
        'motif',
        'statut',
        'justification',
        'piece_justificative',
        'commentaire_interne',
        'source_signalement',
        'visible_parent',
        'enregistre_par',
        'statut_mis_a_jour_par',
        'statut_mis_a_jour_le',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'heure_debut' => 'datetime:H:i',
        'heure_fin' => 'datetime:H:i',
        'heure_arrivee' => 'datetime:H:i',
        'visible_parent' => 'boolean',
        'statut_mis_a_jour_le' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function enregistrePar()
    {
        return $this->belongsTo(User::class, 'enregistre_par')->withTrashed();
    }

    public function statutMisAJourPar()
    {
        return $this->belongsTo(User::class, 'statut_mis_a_jour_par')->withTrashed();
    }


    public function justificationParentale()
    {
        return $this->hasOne(JustificationAbsenceRetard::class, 'absence_retard_id');
    }

    public function libelleType(): string
    {
        return $this->type === 'retard' ? 'Retard' : 'Absence';
    }

    public function libellePeriode(): string
    {
        return match ($this->periode) {
            'matin' => 'Matin',
            'apres_midi' => 'Après-midi',
            'cours' => 'Cours',
            default => 'Journée entière',
        };
    }

    public function libelleStatut(): string
    {
        return match ($this->statut) {
            'justifiee' => 'Justifiée',
            'non_justifiee' => 'Non justifiée',
            'refusee' => 'Refusée',
            default => 'En attente',
        };
    }
}
