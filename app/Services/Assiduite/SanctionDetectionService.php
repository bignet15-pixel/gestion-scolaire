<?php

namespace App\Services\Assiduite;

use App\Models\AbsenceRetard;
use App\Models\Sanction;
use App\Models\SanctionAppliquee;
use App\Models\Trimestre;
use Carbon\Carbon;

class SanctionDetectionService
{
    public function detecter(AbsenceRetard $evenement): void
    {
        $evenement->loadMissing('inscription.anneeScolaire');

        if (! $evenement->inscription) {
            return;
        }

        $sanctions = Sanction::query()
            ->where('active', true)
            ->whereIn('categorie', ['absence', 'retard'])
            ->where('categorie', $evenement->type)
            ->whereIn('mode_declenchement', ['automatique', 'mixte'])
            ->whereNotNull('seuil')
            ->whereNotNull('periode_calcul')
            ->where(function ($query) {
                $query->where('type_effet', '!=', 'points_en_moins')
                    ->orWhere('valeur_effet', '>', 0);
            })
            ->get();

        foreach ($sanctions as $sanction) {
            $this->proposerSiSeuilAtteint($evenement, $sanction);
        }
    }

    private function proposerSiSeuilAtteint(AbsenceRetard $evenement, Sanction $sanction): void
    {
        [$periodeDebut, $periodeFin, $trimestre] = $this->periodeDeCalcul($evenement, $sanction);

        if (! $periodeDebut || ! $periodeFin) {
            return;
        }

        if ($sanction->type_effet === 'points_en_moins' && ! $trimestre) {
            return;
        }

        $nombreEvenements = AbsenceRetard::query()
            ->where('inscription_id', $evenement->inscription_id)
            ->where('type', $evenement->type)
            ->whereBetween('date_debut', [
                $periodeDebut->toDateString(),
                $periodeFin->toDateString(),
            ])
            ->when($sanction->statut_declencheur !== 'tous', function ($query) use ($sanction) {
                $query->where('statut', $sanction->statut_declencheur);
            })
            ->count();

        if ($nombreEvenements < (int) $sanction->seuil) {
            return;
        }

        $propositionExistante = SanctionAppliquee::query()
            ->where('inscription_id', $evenement->inscription_id)
            ->where('sanction_id', $sanction->id)
            ->whereDate('periode_debut', $periodeDebut->toDateString())
            ->whereDate('periode_fin', $periodeFin->toDateString())
            ->whereIn('statut', ['proposee', 'appliquee'])
            ->first();

        if ($propositionExistante) {
            if ($propositionExistante->statut === 'proposee') {
                $propositionExistante->update([
                    'nombre_evenements' => $nombreEvenements,
                ]);
            }

            return;
        }

        SanctionAppliquee::create([
            'inscription_id' => $evenement->inscription_id,
            'sanction_id' => $sanction->id,
            'trimestre_id' => $trimestre?->id,
            'origine' => 'automatique',
            'date_application' => null,
            'periode_debut' => $periodeDebut->toDateString(),
            'periode_fin' => $periodeFin->toDateString(),
            'nombre_evenements' => $nombreEvenements,
            'motif' => 'Seuil automatique atteint : '.$nombreEvenements.' événement(s).',
            'commentaire_interne' => null,
            'statut' => 'proposee',
            'visible_parent' => $sanction->visible_parent_defaut,
            'type_effet' => $sanction->type_effet,
            'valeur_effet' => $sanction->valeur_effet,
            'applique_par' => null,
            'decision_par' => null,
            'decision_le' => null,
        ]);
    }

    private function periodeDeCalcul(AbsenceRetard $evenement, Sanction $sanction): array
    {
        $date = $evenement->date_debut->copy()->startOfDay();
        $inscription = $evenement->inscription;
        $annee = $inscription->anneeScolaire;
        $trimestre = $this->trimestrePourDate($inscription->annee_scolaire_id, $date);

        return match ($sanction->periode_calcul) {
            'semaine' => [
                $date->copy()->startOfWeek(Carbon::MONDAY),
                $date->copy()->endOfWeek(Carbon::SUNDAY),
                $trimestre,
            ],
            'mois' => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
                $trimestre,
            ],
            'trimestre' => $trimestre
                ? [
                    $trimestre->date_debut->copy()->startOfDay(),
                    ($trimestre->date_fin ?? $annee?->date_fin ?? $date->copy()->endOfMonth())
                        ->copy()
                        ->endOfDay(),
                    $trimestre,
                ]
                : [null, null, null],
            'annee' => $annee
                ? [
                    $annee->date_debut->copy()->startOfDay(),
                    $annee->date_fin->copy()->endOfDay(),
                    $trimestre,
                ]
                : [null, null, null],
            default => [null, null, null],
        };
    }

    private function trimestrePourDate(int $anneeScolaireId, Carbon $date): ?Trimestre
    {
        return Trimestre::query()
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->whereDate('date_debut', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('date_fin')
                    ->orWhereDate('date_fin', '>=', $date->toDateString());
            })
            ->orderByDesc('date_debut')
            ->first();
    }
}
