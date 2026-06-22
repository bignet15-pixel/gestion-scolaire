<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\Trimestre;
use App\Services\BulletinService;
use App\Services\ParentAccessService;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

class ParentBulletinController extends Controller
{
    public function __construct(
        private ParentAccessService $parentAccessService
    ) {}

    public function trimestriel(Inscription $inscription, Trimestre $trimestre, BulletinService $bulletinService)
    {
        $this->parentAccessService->assertCanAccessInscription(auth()->user(), $inscription);

        try {
            $data = $bulletinService->bulletinTrimestriel($inscription, $trimestre);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('parent.eleves.show', $inscription->eleve_id)
                ->withErrors(['bulletin' => $exception->getMessage()]);
        }

        $nomFichier = 'bulletin-' . strtolower(str_replace(' ', '-', $inscription->eleve?->matricule ?? 'eleve'))
            . '-' . strtolower(str_replace(' ', '-', $trimestre->nom)) . '.pdf';

        return Pdf::loadView('pdf.bulletin_trimestriel', $data)
            ->setPaper('a4', 'portrait')
            ->download($nomFichier);
    }

    public function annuel(Inscription $inscription, BulletinService $bulletinService)
    {
        $this->parentAccessService->assertCanAccessInscription(auth()->user(), $inscription);

        try {
            $data = $bulletinService->bulletinAnnuel($inscription);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('parent.eleves.show', $inscription->eleve_id)
                ->withErrors(['bulletin' => $exception->getMessage()]);
        }

        $nomFichier = 'bulletin-annuel-' . strtolower(str_replace(' ', '-', $inscription->eleve?->matricule ?? 'eleve')) . '.pdf';

        return Pdf::loadView('pdf.bulletin_annuel', $data)
            ->setPaper('a4', 'portrait')
            ->download($nomFichier);
    }
}
