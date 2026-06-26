<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaiementDeclare extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'paiements_declares';

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDE = 'valide';
    public const STATUT_REFUSE = 'refuse';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_VALIDE,
        self::STATUT_REFUSE,
    ];

    public const MODES_PAIEMENT = [
        'especes',
        'mobile_money',
        'virement',
        'autre',
    ];

    protected $fillable = [
        'inscription_id',
        'parent_id',
        'montant',
        'mode_paiement',
        'numero_transfert',
        'reference_transaction',
        'preuve_paiement',
        'statut',
        'valide_par',
        'valide_le',
        'paiement_id',
        'commentaire_validation',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'valide_le' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id')->withTrashed();
    }

    public function validePar()
    {
        return $this->belongsTo(User::class, 'valide_par')->withTrashed();
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function estValide(): bool
    {
        return $this->statut === self::STATUT_VALIDE;
    }

    public function estRefuse(): bool
    {
        return $this->statut === self::STATUT_REFUSE;
    }

    public function libelleStatut(): string
    {
        return match ($this->statut) {
            self::STATUT_VALIDE => 'Validé',
            self::STATUT_REFUSE => 'Refusé',
            default => 'En attente',
        };
    }
}
