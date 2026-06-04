<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Inscription;
use Illuminate\Http\Request;

class ImpayeController extends Controller
{
    /**
     * Affiche la liste des élèves en impayé.
     */
    public function index(Request $request)
    {
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $selectedClasseId = $request->input('classe_id');

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $classes = Classe::with('anneeScolaire')
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->orderBy('niveau')
            ->orderBy('nom')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Toutes les inscriptions selon le filtre
        |--------------------------------------------------------------------------
        |
        | Ici, on récupère toutes les inscriptions de l'année/classe choisie.
        | On ne filtre pas encore les impayés.
        |
        */

        $query = Inscription::with([
                'eleve',
                'classe.anneeScolaire',
                'anneeScolaire',
            ])
            ->withSum('paiements as total_paye', 'montant');

        if (! empty($selectedAnneeId)) {
            $query->where('annee_scolaire_id', $selectedAnneeId);
        }

        if (! empty($selectedClasseId)) {
            $query->where('classe_id', $selectedClasseId);
        }

        $toutesLesInscriptions = $query
            ->get()
            ->map(function ($inscription) {
                $totalPaye = (float) ($inscription->total_paye ?? 0);
                $fraisAttendu = (float) $inscription->frais_attendu;

                $reste = $fraisAttendu - $totalPaye;

                if ($reste < 0) {
                    $reste = 0;
                }

                $inscription->total_paye_calcule = $totalPaye;
                $inscription->reste_calcule = $reste;

                return $inscription;
            });

        /*
        |--------------------------------------------------------------------------
        | Statistiques globales
        |--------------------------------------------------------------------------
        |
        | Ces chiffres concernent toutes les inscriptions du filtre.
        | 
        |
        */

        $nombreTotalInscriptions = $toutesLesInscriptions->count();

        $totalFraisAttendus = $toutesLesInscriptions->sum('frais_attendu');

        $totalFraisCollectes = $toutesLesInscriptions->sum('total_paye_calcule');

        $totalRestant = $toutesLesInscriptions->sum('reste_calcule');

        $nombreSoldes = $toutesLesInscriptions
            ->filter(function ($inscription) {
                return $inscription->frais_attendu > 0
                    && $inscription->reste_calcule <= 0;
            })
            ->count();

        $tauxRecouvrement = $totalFraisAttendus > 0
            ? round(($totalFraisCollectes / $totalFraisAttendus) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Liste des impayés uniquement
        |--------------------------------------------------------------------------
        |
        | Ici, on filtre seulement ceux qui ont encore un reste à payer.
        |
        */

        $inscriptions = $toutesLesInscriptions
            ->filter(function ($inscription) {
                return $inscription->reste_calcule > 0;
            })
            ->sortBy([
                ['classe.nom', 'asc'],
                ['eleve.nom', 'asc'],
                ['eleve.prenom', 'asc'],
            ]);

        $nombreImpayes = $inscriptions->count();

        return view('impayes.index', compact(
            'annees',
            'classes',
            'inscriptions',
            'selectedAnneeId',
            'selectedClasseId',
            'nombreTotalInscriptions',
            'nombreImpayes',
            'nombreSoldes',
            'totalFraisAttendus',
            'totalFraisCollectes',
            'totalRestant',
            'tauxRecouvrement'
        ));
    }
}