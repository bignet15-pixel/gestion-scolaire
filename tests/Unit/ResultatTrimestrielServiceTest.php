<?php

namespace Tests\Unit;

use App\Services\ResultatTrimestrielService;
use PHPUnit\Framework\TestCase;

class ResultatTrimestrielServiceTest extends TestCase
{
    public function test_il_moyenne_les_evaluations_par_matiere_avant_appliquer_le_coefficient(): void
    {
        $service = new ResultatTrimestrielService;
        $notes = collect([
            $this->note(10, 20, 1, 1, 2),
            $this->note(15, 20, 1, 1, 2),
            $this->note(16, 20, 2, 1, 3),
        ]);

        $totalPondere = $service->calculerTotalPondereParMatiere($notes, 1);

        // Matière 1 : ((10 + 15) / 2) × 2 = 25
        // Matière 2 : 16 × 3 = 48
        $this->assertSame(73.0, $totalPondere);
        $this->assertSame(14.6, round($totalPondere / 5, 2));
    }

    public function test_il_ramene_chaque_note_sur_vingt_et_filtre_le_trimestre(): void
    {
        $service = new ResultatTrimestrielService;
        $notes = collect([
            $this->note(8, 10, 1, 1, 2),
            $this->note(20, 20, 1, 2, 2),
        ]);

        $totalPondere = $service->calculerTotalPondereParMatiere($notes, 1);

        $this->assertSame(32.0, $totalPondere);
    }

    private function note(
        float $valeur,
        float $bareme,
        int $matiereId,
        int $trimestreId,
        float $coefficient
    ): object {
        return (object) [
            'valeur' => $valeur,
            'evaluation' => (object) [
                'bareme' => $bareme,
                'matiere_id' => $matiereId,
                'trimestre_id' => $trimestreId,
                'coefficient' => $coefficient,
            ],
        ];
    }
}
