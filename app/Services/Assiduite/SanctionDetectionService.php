<?php

namespace App\Services\Assiduite;

use App\Models\AbsenceRetard;
use App\Models\Sanction;
use App\Models\SanctionAppliquee;
use App\Models\Trimestre;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
            $this->synchroniserProposition($evenement, $sanction);
        }
    }

    private function synchroniserProposition(AbsenceRetard $evenement, Sanction $sanction): void
    {
        [$periodeDebut, $periodeFin, $trimestre] = $this->periodeDeCalcul($evenement, $sanction);

        if (! $periodeDebut || ! $periodeFin) {
            return;
        }

        if ($sanction->type_effet === 'points_en_moins' && ! $trimestre) {
            return;
        }

        $propositionActive = $this->sanctionActivePourPeriode($evenement, $sanction, $periodeDebut, $periodeFin);
        $resetAt = $this->dernierResetCompteur($evenement, $sanction, $periodeDebut, $periodeFin);
        $nombreEvenements = $this->nombreEvenementsEligibles($evenement, $sanction, $periodeDebut, $periodeFin, $resetAt);
        $seuil = (int) $sanction->seuil;

        if ($nombreEvenements < $seuil) {
            if ($propositionActive?->statut === 'proposee') {
                $this->annulerPropositionObsolete($propositionActive, $nombreEvenements, $seuil);
            }

            return;
        }

        if ($propositionActive) {
            if ($propositionActive->statut === 'proposee') {
                $propositionActive->update([
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
            'commentaire_interne' => $resetAt
                ? 'Compteur relancé après une décision précédente du '.$resetAt->format('d/m/Y H:i').'.'
                : null,
            'statut' => 'proposee',
            'visible_parent' => $sanction->visible_parent_defaut,
            'type_effet' => $sanction->type_effet,
            'valeur_effet' => $sanction->valeur_effet,
            'applique_par' => null,
            'decision_par' => null,
            'decision_le' => null,
        ]);
    }

    private function sanctionActivePourPeriode(
        AbsenceRetard $evenement,
        Sanction $sanction,
        Carbon $periodeDebut,
        Carbon $periodeFin
    ): ?SanctionAppliquee {
        return SanctionAppliquee::query()
            ->where('inscription_id', $evenement->inscription_id)
            ->where('sanction_id', $sanction->id)
            ->whereDate('periode_debut', $periodeDebut->toDateString())
            ->whereDate('periode_fin', $periodeFin->toDateString())
            ->whereIn('statut', ['proposee', 'appliquee'])
            ->latest('id')
            ->first();
    }

    private function dernierResetCompteur(
        AbsenceRetard $evenement,
        Sanction $sanction,
        Carbon $periodeDebut,
        Carbon $periodeFin
    ): ?Carbon {
        $decision = SanctionAppliquee::query()
            ->where('inscription_id', $evenement->inscription_id)
            ->where('sanction_id', $sanction->id)
            ->whereDate('periode_debut', $periodeDebut->toDateString())
            ->whereDate('periode_fin', $periodeFin->toDateString())
            ->whereIn('statut', ['ignoree', 'annulee', 'terminee'])
            ->whereNotNull('decision_le')
            ->latest('decision_le')
            ->value('decision_le');

        return $decision ? Carbon::parse($decision) : null;
    }

    private function nombreEvenementsEligibles(
        AbsenceRetard $evenement,
        Sanction $sanction,
        Carbon $periodeDebut,
        Carbon $periodeFin,
        ?Carbon $resetAt = null
    ): int {
        return AbsenceRetard::query()
            ->where('inscription_id', $evenement->inscription_id)
            ->where('type', $evenement->type)
            ->whereBetween('date_debut', [
                $periodeDebut->toDateString(),
                $periodeFin->toDateString(),
            ])
            ->when($sanction->statut_declencheur !== 'tous', function (Builder $query) use ($sanction) {
                $query->where('statut', $sanction->statut_declencheur);
            })
            ->when($resetAt, function (Builder $query) use ($resetAt) {
                $query->where(function (Builder $q) use ($resetAt) {
                    $q->where('created_at', '>', $resetAt)
                        ->orWhere('updated_at', '>', $resetAt);
                });
            })
            ->count();
    }

    private function annulerPropositionObsolete(SanctionAppliquee $proposition, int $nombreEvenements, int $seuil): void
    {
        $message = 'Proposition annulée automatiquement : seuil non atteint après recalcul '
            .'('.$nombreEvenements.'/'.$seuil.' événement(s)).';

        $commentaire = trim(($proposition->commentaire_interne ? $proposition->commentaire_interne."\n" : '').$message);

        $proposition->update([
            'statut' => 'annulee',
            'nombre_evenements' => $nombreEvenements,
            'commentaire_interne' => $commentaire,
            'decision_par' => Auth::id(),
            'decision_le' => now(),
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
