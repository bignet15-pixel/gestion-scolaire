<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DemandeReinscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'demandes_reinscription';

    public const TYPE_PASSAGE_SUPERIEUR = 'passage_superieur';
    public const TYPE_REDOUBLEMENT = 'redoublement';

    public const DECISION_PASSAGE_AUTORISE = 'passage_autorise';
    public const DECISION_REDOUBLEMENT_OBLIGATOIRE = 'redoublement_obligatoire';
    public const DECISION_NON_DISPONIBLE = 'non_disponible';

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDEE = 'validee';
    public const STATUT_REFUSEE = 'refusee';
    public const STATUT_ANNULEE = 'annulee';

    public const TYPES = [
        self::TYPE_PASSAGE_SUPERIEUR,
        self::TYPE_REDOUBLEMENT,
    ];

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_VALIDEE,
        self::STATUT_REFUSEE,
        self::STATUT_ANNULEE,
    ];

    protected $fillable = [
        'eleve_id',
        'parent_id',
        'ancienne_inscription_id',
        'ancienne_classe_id',
        'nouvelle_annee_scolaire_id',
        'classe_demandee_id',
        'type_demande',
        'decision_systeme',
        'statut',
        'inscription_creee_id',
        'commentaire_parent',
        'valide_par',
        'valide_le',
        'commentaire_gestionnaire',
    ];

    protected $casts = [
        'valide_le' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id')->withTrashed();
    }

    public function ancienneInscription()
    {
        return $this->belongsTo(Inscription::class, 'ancienne_inscription_id')->withTrashed();
    }

    public function ancienneClasse()
    {
        return $this->belongsTo(Classe::class, 'ancienne_classe_id')->withTrashed();
    }

    public function nouvelleAnneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'nouvelle_annee_scolaire_id');
    }

    public function classeDemandee()
    {
        return $this->belongsTo(Classe::class, 'classe_demandee_id')->withTrashed();
    }

    public function inscriptionCreee()
    {
        return $this->belongsTo(Inscription::class, 'inscription_creee_id')->withTrashed();
    }

    public function validePar()
    {
        return $this->belongsTo(User::class, 'valide_par')->withTrashed();
    }

    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function estValidee(): bool
    {
        return $this->statut === self::STATUT_VALIDEE;
    }

    public function estRefusee(): bool
    {
        return $this->statut === self::STATUT_REFUSEE;
    }

    public function estAnnulee(): bool
    {
        return $this->statut === self::STATUT_ANNULEE;
    }

    public function libelleTypeDemande(): string
    {
        return match ($this->type_demande) {
            self::TYPE_REDOUBLEMENT => 'Réinscription dans la même classe',
            default => 'Passage en classe supérieure',
        };
    }

    public function libelleStatut(): string
    {
        return match ($this->statut) {
            self::STATUT_VALIDEE => 'Validée',
            self::STATUT_REFUSEE => 'Refusée',
            self::STATUT_ANNULEE => 'Annulée',
            default => 'En attente',
        };
    }
}
