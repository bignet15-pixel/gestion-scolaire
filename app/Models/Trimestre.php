<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trimestre extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'annee_scolaire_id',
        'nom',
        'date_debut',
        'date_fin',
        'statut',
        'is_deleted',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function estActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function estFerme(): bool
    {
        return $this->statut === 'ferme';
    }

    public function statutPedagogique(): string
    {
        if ($this->estFerme()) {
            return 'passe';
        }

        $aujourdhui = now()->startOfDay();

        if (! $this->date_debut || $aujourdhui->lt($this->date_debut->copy()->startOfDay())) {
            return 'pas_encore_programme';
        }

        return 'en_cours';
    }

    public function libelleStatutPedagogique(): string
    {
        return match ($this->statutPedagogique()) {
            'passe' => 'Passé',
            'en_cours' => 'En cours',
            default => 'Pas encore programmé',
        };
    }

    public function badgeStatutPedagogique(): string
    {
        return match ($this->statutPedagogique()) {
            'passe' => 'badge-success',
            'en_cours' => 'badge-warning',
            default => 'badge-muted',
        };
    }

    public static function fermerTrimestresArrives(): int
    {
        $trimestres = static::query()
            ->where('statut', 'actif')
            ->whereNotNull('date_fin')
            ->whereDate('date_fin', '<=', now()->toDateString())
            ->get();

        $nombreFermes = 0;

        foreach ($trimestres as $trimestre) {
            if (! $trimestre->peutEtreFerme()) {
                continue;
            }

            $trimestre->update([
                'statut' => 'ferme',
            ]);

            $nombreFermes++;
        }

        return $nombreFermes;
    }

    public function peutEtreFerme(): bool
    {
        return $this->nombreNotesManquantes() === 0;
    }

    public function nombreNotesManquantes(): int
    {
        $evaluations = $this->evaluations()
            ->select(['id', 'classe_id', 'trimestre_id'])
            ->get();

        $totalManquant = 0;

        foreach ($evaluations as $evaluation) {
            $nombreEleves = Inscription::where('classe_id', $evaluation->classe_id)
                ->where('annee_scolaire_id', $this->annee_scolaire_id)
                ->where('statut', 'actif')
                ->count();

            if ($nombreEleves === 0) {
                continue;
            }

            $nombreNotesSaisies = Note::where('evaluation_id', $evaluation->id)
                ->whereNotNull('valeur')
                ->whereHas('inscription', function ($query) use ($evaluation) {
                    $query->where('classe_id', $evaluation->classe_id)
                        ->where('annee_scolaire_id', $this->annee_scolaire_id)
                        ->where('statut', 'actif');
                })
                ->count();

            $totalManquant += max(0, $nombreEleves - $nombreNotesSaisies);
        }

        return $totalManquant;
    }

    public function messageBlocageFermeture(): ?string
    {
        $nombreNotesManquantes = $this->nombreNotesManquantes();

        if ($nombreNotesManquantes === 0) {
            return null;
        }

        return 'Impossible de fermer ce trimestre : ' . $nombreNotesManquantes . ' note(s) attendue(s) ne sont pas encore saisie(s).';
    }
}
