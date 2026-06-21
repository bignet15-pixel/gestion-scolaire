<?php

namespace App\Services;

use App\Models\ClasseMatiereUser;
use App\Models\SanctionAppliquee;

class ResultatTrimestrielService
{
    /**
     * Calcule le total pondéré en moyennant d'abord les évaluations
     * d'une même matière, puis en appliquant une seule fois son coefficient.
     */
    public function calculerTotalPondereParMatiere(iterable $notes, ?int $trimestreId = null): float
    {
        $lignes = collect($notes)
            ->filter(function ($note) use ($trimestreId) {
                $evaluation = $note->evaluation;

                return $evaluation
                    && $note->valeur !== null
                    && (float) $evaluation->bareme > 0
                    && ($trimestreId === null
                        || (int) $evaluation->trimestre_id === $trimestreId);
            })
            ->map(function ($note) {
                $evaluation = $note->evaluation;

                return [
                    'matiere_id' => (int) $evaluation->matiere_id,
                    'note_sur_20' => ((float) $note->valeur / (float) $evaluation->bareme) * 20,
                    'coefficient' => (float) $evaluation->coefficient,
                ];
            });

        return $this->calculerTotalPondereDepuisLignes($lignes);
    }

    /**
     * @param  iterable<array{matiere_id:int, note_sur_20:float, coefficient:float}>  $lignes
     */
    public function calculerTotalPondereDepuisLignes(iterable $lignes): float
    {
        return (float) collect($lignes)
            ->groupBy('matiere_id')
            ->sum(function ($lignesMatiere) {
                $moyenneMatiere = $lignesMatiere->avg('note_sur_20');
                $coefficient = (float) $lignesMatiere->first()['coefficient'];

                return $moyenneMatiere * $coefficient;
            });
    }

    /**
     * Additionne une seule fois le coefficient de chaque matière d'une classe.
     */
    public function totalCoefficientsClasse(int $classeId): float
    {
        return (float) ClasseMatiereUser::query()
            ->where('classe_id', $classeId)
            ->whereIn('statut', ['actif', 'termine'])
            ->orderByRaw("CASE statut WHEN 'actif' THEN 1 ELSE 2 END")
            ->orderByDesc('date_debut')
            ->get(['matiere_id', 'coefficient'])
            ->unique('matiere_id')
            ->sum('coefficient');
    }

    public function appliquerRetenues(
        int $inscriptionId,
        int $trimestreId,
        float $totalPondere,
        float $totalCoefficients
    ): array {
        $totalPointsEnMoinsEnCours = SanctionAppliquee::totalPointsEnMoinsEnCoursPour(
            $inscriptionId,
            $trimestreId
        );

        $totalPointsEnMoinsDefinitifs = SanctionAppliquee::totalPointsEnMoinsDefinitifsPour(
            $inscriptionId,
            $trimestreId
        );

        $totalPointsEnMoinsVisibles = $totalPointsEnMoinsEnCours + $totalPointsEnMoinsDefinitifs;

        // Seules les sanctions terminées modifient réellement le total pondéré.
        $totalPondereFinal = max(0, $totalPondere - $totalPointsEnMoinsDefinitifs);

        return [
            'total_pondere' => round($totalPondere, 2),

            // Points affichés dans les vues.
            'total_points_en_moins_visibles' => round($totalPointsEnMoinsVisibles, 2),
            'total_points_en_moins_en_cours' => round($totalPointsEnMoinsEnCours, 2),
            'total_points_en_moins_definitifs' => round($totalPointsEnMoinsDefinitifs, 2),

            // Compatibilité : total_points_en_moins = points définitifs.
            'total_points_en_moins' => round($totalPointsEnMoinsDefinitifs, 2),

            'total_pondere_final' => round($totalPondereFinal, 2),
            'total_coefficients' => round($totalCoefficients, 2),
            'moyenne_avant_sanction' => $totalCoefficients > 0
                ? round($totalPondere / $totalCoefficients, 2)
                : null,
            'moyenne_finale' => $totalCoefficients > 0
                ? round($totalPondereFinal / $totalCoefficients, 2)
                : null,
        ];
    }
}
