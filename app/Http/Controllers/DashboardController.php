<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\Eleve;
use App\Models\EmploiDuTemps;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Redirige vers le dashboard correspondant au rôle connecté.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->estGestionnaire()) {
            return $this->dashboardGestionnaire();
        }

        if ($user->estEnseignant()) {
            return $this->dashboardEnseignant($user);
        }

        abort(403, 'Rôle non autorisé.');
    }

    /**
     * Dashboard global du gestionnaire.
     */
    private function dashboardGestionnaire()
    {
        $nombreEleves = Eleve::count();

        $nombreClasses = Classe::count();

        $nombreEnseignants = User::where('role', 'enseignant')->count();

        $nombreInscriptions = Inscription::count();

        $nombreEvaluations = Evaluation::count();

        /*
        |--------------------------------------------------------------------------
        | Calcul financier global fiable
        |--------------------------------------------------------------------------
        |
        | On récupère toutes les inscriptions.
        | Pour chaque inscription, on calcule :
        | - frais attendu
        | - total payé
        | - reste à payer
        |
        */

        $inscriptionsFinance = Inscription::withSum('paiements as total_paye', 'montant')
            ->get()
            ->map(function ($inscription) {
                $fraisAttendu = (float) $inscription->frais_attendu;

                $totalPaye = (float) ($inscription->total_paye ?? 0);

                $reste = $fraisAttendu - $totalPaye;

                if ($reste < 0) {
                    $reste = 0;
                }

                $inscription->total_paye_calcule = $totalPaye;
                $inscription->reste_calcule = $reste;

                return $inscription;
            });

        $totalFraisAttendus = $inscriptionsFinance->sum('frais_attendu');

        $totalFraisCollectes = $inscriptionsFinance->sum('total_paye_calcule');

        $totalRestant = $inscriptionsFinance->sum('reste_calcule');

        $nombreImpayes = $inscriptionsFinance
            ->filter(function ($inscription) {
                return $inscription->reste_calcule > 0;
            })
            ->count();

        $nombreSoldes = $inscriptionsFinance
            ->filter(function ($inscription) {
                return $inscription->frais_attendu > 0
                    && $inscription->reste_calcule <= 0;
            })
            ->count();

        $tauxRecouvrement = $totalFraisAttendus > 0
            ? round(($totalFraisCollectes / $totalFraisAttendus) * 100, 2)
            : 0;

        $classes = Classe::with(['anneeScolaire', 'enseignantPrincipal'])
            ->withCount('inscriptions')
            ->orderBy('annee_scolaire_id')
            ->orderBy('niveau')
            ->get();

        $derniersPaiements = Paiement::with([
                'inscription.eleve',
                'inscription.classe',
                'gestionnaire',
            ])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.gestionnaire', compact(
            'nombreEleves',
            'nombreClasses',
            'nombreEnseignants',
            'nombreInscriptions',
            'nombreEvaluations',
            'totalFraisAttendus',
            'totalFraisCollectes',
            'totalRestant',
            'nombreImpayes',
            'nombreSoldes',
            'tauxRecouvrement',
            'classes',
            'derniersPaiements'
        ));
    }

    /**
     * Dashboard personnel de l’enseignant connecté.
     */
    private function dashboardEnseignant(User $user)
    {
        $affectations = ClasseMatiereUser::with([
                'classe.anneeScolaire',
                'matiere',
            ])
            ->where('user_id', $user->id)
            ->where('statut', 'actif')
            ->get();

        $classeIds = $affectations
            ->pluck('classe_id')
            ->unique()
            ->values();

        $matiereIds = $affectations
            ->pluck('matiere_id')
            ->unique()
            ->values();

        $nombreClasses = $classeIds->count();

        $nombreMatieres = $matiereIds->count();

        $nombreEleves = Inscription::whereIn('classe_id', $classeIds)
            ->where('statut', 'actif')
            ->distinct('eleve_id')
            ->count('eleve_id');

        $nombreEvaluations = Evaluation::whereIn('classe_id', $classeIds)
            ->whereIn('matiere_id', $matiereIds)
            ->count();

        $emploisDuTemps = EmploiDuTemps::with([
                'affectation.classe',
                'affectation.matiere',
            ])
            ->whereHas('affectation', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderByRaw("FIELD(jour, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi')")
            ->orderBy('heure_debut')
            ->get();

        $evaluationsRecentes = Evaluation::with(['classe', 'matiere', 'trimestre'])
            ->whereIn('classe_id', $classeIds)
            ->whereIn('matiere_id', $matiereIds)
            ->orderByDesc('date_evaluation')
            ->limit(5)
            ->get();

        return view('dashboard.enseignant', compact(
            'affectations',
            'nombreClasses',
            'nombreMatieres',
            'nombreEleves',
            'nombreEvaluations',
            'emploisDuTemps',
            'evaluationsRecentes'
        ));
    }
}