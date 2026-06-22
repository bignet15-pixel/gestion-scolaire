<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JustificationAbsenceRetard extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'justifications_absence_retard';

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_ACCEPTEE = 'acceptee';
    public const STATUT_REFUSEE = 'refusee';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_ACCEPTEE,
        self::STATUT_REFUSEE,
    ];

    protected $fillable = [
        'absence_retard_id',
        'parent_id',
        'motif',
        'message',
        'piece_jointe',
        'statut',
        'traite_par',
        'traite_le',
        'commentaire_traitement',
    ];

    protected $casts = [
        'traite_le' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function absenceRetard()
    {
        return $this->belongsTo(AbsenceRetard::class, 'absence_retard_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id')->withTrashed();
    }

    public function traitePar()
    {
        return $this->belongsTo(User::class, 'traite_par')->withTrashed();
    }

    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function estAcceptee(): bool
    {
        return $this->statut === self::STATUT_ACCEPTEE;
    }

    public function estRefusee(): bool
    {
        return $this->statut === self::STATUT_REFUSEE;
    }

    public function libelleStatut(): string
    {
        return match ($this->statut) {
            self::STATUT_ACCEPTEE => 'Acceptée',
            self::STATUT_REFUSEE => 'Refusée',
            default => 'En attente',
        };
    }
}
