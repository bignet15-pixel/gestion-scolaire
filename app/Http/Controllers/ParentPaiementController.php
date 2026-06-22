<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Services\ParentAccessService;
use Barryvdh\DomPDF\Facade\Pdf;

class ParentPaiementController extends Controller
{
    public function __construct(
        private ParentAccessService $parentAccessService
    ) {}

    public function recu(Paiement $paiement)
    {
        $paiement->load([
            'inscription.eleve',
            'inscription.classe',
            'inscription.anneeScolaire',
            'gestionnaire',
        ]);

        $this->parentAccessService->assertCanAccessInscription(auth()->user(), $paiement->inscription);

        $pdf = Pdf::loadView('pdf.recu_paiement', [
            'paiement' => $paiement,
        ]);

        return $pdf->download('recu-' . $paiement->numero_paiement . '.pdf');
    }
}
