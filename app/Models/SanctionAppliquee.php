<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SanctionAppliquee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sanctions_appliquees';

    public const ORIGINES = ['automatique', 'manuel'];

    public const STATUTS = ['proposee', 'appliquee', 'ignoree', 'annulee', 'terminee'];

    public const TYPES_EFFET = Sanction::TYPES_EFFET;

    protected $fillable = [
        'inscription_id',
        'sanction_id',
        'trimestre_id',
        'origine',
        'date_application',
        'periode_debut',
        'periode_fin',
        'nombre_evenements',
        'motif',
        'commentaire_interne',
        'statut',
        'visible_parent',
        'type_effet',
        'valeur_effet',
        'applique_par',
        'decision_par',
        'decision_le',
    ];

    protected $casts = [
        'date_application' => 'date',
        'periode_debut' => 'date',
        'periode_fin' => 'date',
        'visible_parent' => 'boolean',
        'valeur_effet' => 'decimal:2',
        'decision_le' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function sanction()
    {
        return $this->belongsTo(Sanction::class)->withTrashed();
    }

    public function trimestre()
    {
        return $this->belongsTo(Trimestre::class)->withTrashed();
    }

    public function appliquePar()
    {
        return $this->belongsTo(User::class, 'applique_par')->withTrashed();
    }

    public function decisionPar()
    {
        return $this->belongsTo(User::class, 'decision_par')->withTrashed();
    }

    /**
     * Points en moins visibles, mais pas encore définitifs.
     * Statut appliquee = sanction en cours.
     */
    public static function totalPointsEnMoinsEnCoursPour(int $inscriptionId, int $trimestreId): float
    {
        return (float) static::query()
            ->where('inscription_id', $inscriptionId)
            ->where('trimestre_id', $trimestreId)
            ->where('statut', 'appliquee')
            ->where('type_effet', 'points_en_moins')
            ->sum('valeur_effet');
    }

    /**
     * Points en moins réellement appliqués au calcul.
     * Statut terminee = sanction clôturée et effet définitif.
     */
    public static function totalPointsEnMoinsDefinitifsPour(int $inscriptionId, int $trimestreId): float
    {
        return (float) static::query()
            ->where('inscription_id', $inscriptionId)
            ->where('trimestre_id', $trimestreId)
            ->where('statut', 'terminee')
            ->where('type_effet', 'points_en_moins')
            ->sum('valeur_effet');
    }

    /**
     * Compatibilité avec l'ancien code : cette méthode retourne uniquement
     * les points définitifs, donc ceux des sanctions terminées.
     */
    public static function totalPointsEnMoinsPour(int $inscriptionId, int $trimestreId): float
    {
        return static::totalPointsEnMoinsDefinitifsPour($inscriptionId, $trimestreId);
    }
}
