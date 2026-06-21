<?php

namespace App\Services;

use App\Models\SanctionAppliquee;

class ResultatTrimestrielService
{
    public function appliquerRetenues(
        int $inscriptionId,
        int $trimestreId,
        float $totalPondere,
        float $totalCoefficients
    ): array {
        $totalPointsEnMoins = SanctionAppliquee::totalPointsEnMoinsPour(
            $inscriptionId,
            $trimestreId
        );
        $totalPondereFinal = max(0, $totalPondere - $totalPointsEnMoins);

        return [
            'total_pondere' => round($totalPondere, 2),
            'total_points_en_moins' => round($totalPointsEnMoins, 2),
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
