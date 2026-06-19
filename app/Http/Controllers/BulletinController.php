<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\Trimestre;
use App\Services\BulletinService;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

class BulletinController extends Controller
{
    public function trimestriel(
        Inscription $inscription,
        Trimestre $trimestre,
        BulletinService $bulletinService
    ) {
        try {
            $data = $bulletinService->bulletinTrimestriel($inscription, $trimestre);
        } catch (RuntimeException $exception) {
            return $this->retourEleveAvecErreur($inscription, $exception->getMessage());
        }

        $nomFichier = 'bulletin-' . strtolower(str_replace(' ', '-', $inscription->eleve?->matricule ?? 'eleve'))
            . '-' . strtolower(str_replace(' ', '-', $trimestre->nom)) . '.pdf';

        return Pdf::loadView('pdf.bulletin_trimestriel', $data)
            ->setPaper('a4', 'portrait')
            ->download($nomFichier);
    }

    public function annuel(Inscription $inscription, BulletinService $bulletinService)
    {
        try {
            $data = $bulletinService->bulletinAnnuel($inscription);
        } catch (RuntimeException $exception) {
            return $this->retourEleveAvecErreur($inscription, $exception->getMessage());
        }

        $nomFichier = 'bulletin-annuel-' . strtolower(str_replace(' ', '-', $inscription->eleve?->matricule ?? 'eleve')) . '.pdf';

        return Pdf::loadView('pdf.bulletin_annuel', $data)
            ->setPaper('a4', 'portrait')
            ->download($nomFichier);
    }

    private function retourEleveAvecErreur(Inscription $inscription, string $message)
    {
        return redirect()
            ->route('eleves.show', [
                'eleve' => $inscription->eleve_id,
                'annee_scolaire_id' => $inscription->annee_scolaire_id,
                'classe_id' => $inscription->classe_id,
            ])
            ->withErrors(['bulletin' => $message]);
    }
}
