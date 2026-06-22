<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\AbsenceRetard;
use App\Models\EmploiDuTemps;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\PaiementDeclare;
use App\Models\DemandeReinscription;
use App\Models\SanctionAppliquee;
use App\Models\User;
use App\Models\AnneeScolaire;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Redirige vers le dashboard correspondant au rôle connecté.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->estGestionnaire()) {
            return $this->dashboardGestionnaire($request);
        }

        if ($user->estEnseignant()) {
            return $this->dashboardEnseignant($user, $request);
        }

        if ($user->estParent()) {
            return $this->dashboardParent($user);
        }

        abort(403, 'Rôle non autorisé.');
    }

    /**
     * Dashboard personnel du parent connecté.
     *
     * Par défaut, les indicateurs financiers utilisent uniquement l'année
     * scolaire courante. L'historique complet reste consultable dans la fiche
     * de chaque enfant.
     */
    private function dashboardParent(User $user)
    {
        $anneeActive = $this->anneeScolaireCourante();

        $enfants = $user->enfants()
            ->with([
                'inscriptions' => function ($query) use ($anneeActive) {
                    $query->with(['classe.anneeScolaire', 'anneeScolaire', 'paiements'])
                        ->when($anneeActive, fn ($q) => $q->where('annee_scolaire_id', $anneeActive->id))
                        ->orderByDesc('date_inscription');
                },
            ])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $inscriptionsActives = $enfants
            ->flatMap(fn ($eleve) => $eleve->inscriptions)
            ->values();

        $inscriptionIds = $inscriptionsActives->pluck('id');

        $totalFraisAttendus = (float) $inscriptionsActives->sum('frais_attendu');

        $totalFraisCollectes = (float) $inscriptionsActives->sum(function ($inscription) {
            return $inscription->paiements->sum('montant');
        });

        $totalRestant = max(0, $totalFraisAttendus - $totalFraisCollectes);

        $absencesRetards = AbsenceRetard::with([
                'inscription.eleve',
                'inscription.classe',
                'justificationParentale',
            ])
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('visible_parent', true)
            ->orderByDesc('date_debut')
            ->limit(8)
            ->get();

        $sanctions = SanctionAppliquee::with([
                'inscription.eleve',
                'inscription.classe',
                'sanction',
                'trimestre',
            ])
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('visible_parent', true)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $paiementsDeclares = PaiementDeclare::with([
                'inscription.eleve',
                'inscription.classe',
            ])
            ->whereIn('inscription_id', $inscriptionIds)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $demandesReinscription = DemandeReinscription::with([
                'eleve',
                'classeDemandee',
                'nouvelleAnneeScolaire',
            ])
            ->whereIn('eleve_id', $enfants->pluck('id'))
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return view('dashboard.parent', compact(
            'anneeActive',
            'enfants',
            'inscriptionsActives',
            'totalFraisAttendus',
            'totalFraisCollectes',
            'totalRestant',
            'absencesRetards',
            'sanctions',
            'paiementsDeclares',
            'demandesReinscription'
        ));
    }

    /**
     * Dashboard global du gestionnaire.
     */
    private function dashboardGestionnaire(Request $request)
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $selectedAnneeId = $request->input('annee_scolaire_id');

        if (! $selectedAnneeId && $annees->isNotEmpty()) {
            $selectedAnneeId = $this->anneeScolaireCourante()?->id ?? $annees->first()->id;
        }

        $annee = $selectedAnneeId
            ? $annees->first(fn ($annee) => (string) $annee->id === (string) $selectedAnneeId)
            : null;

        $nombreEleves = Inscription::when($annee, function ($query) use ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            })
            ->where('statut', 'actif')
            ->distinct('eleve_id')
            ->count('eleve_id');

        $nombreClasses = Classe::when($annee, function ($query) use ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            })
            ->count();

        $enseignantPrincipalIds = Classe::when($annee, function ($query) use ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            })
            ->whereNotNull('enseignant_principal_id')
            ->pluck('enseignant_principal_id');

        $enseignantAffectationIds = ClasseMatiereUser::whereIn('statut', ['actif', 'termine'])
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('classe', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->pluck('user_id');

        $nombreEnseignants = $enseignantPrincipalIds
            ->merge($enseignantAffectationIds)
            ->unique()
            ->count();

        $nombreInscriptions = Inscription::when($annee, function ($query) use ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            })
            ->count();

        $nombreEvaluations = Evaluation::when($annee, function ($query) use ($annee) {
                $query->whereHas('classe', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->count();

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
            ->when($annee, function ($query) use ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            })
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
            ->when($annee, function ($query) use ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            })
            ->orderBy('annee_scolaire_id')
            ->orderBy('niveau')
            ->get();

        $derniersPaiements = Paiement::with([
                'inscription.eleve',
                'inscription.classe',
                'gestionnaire',
            ])
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('inscription', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.gestionnaire', compact(
            'annees',
            'annee',
            'selectedAnneeId',
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
    private function dashboardEnseignant(User $user, Request $request)
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $selectedAnneeId = $request->input('annee_scolaire_id');

        if (! $selectedAnneeId && $annees->isNotEmpty()) {
            $selectedAnneeId = $this->anneeScolaireCourante()?->id ?? $annees->first()->id;
        }

        $annee = $selectedAnneeId
            ? $annees->first(fn ($annee) => (string) $annee->id === (string) $selectedAnneeId)
            : null;

        $debutSemaine = now()->startOfWeek(Carbon::MONDAY);
        $finSemaine = now()->endOfWeek(Carbon::SATURDAY);

        $affectations = ClasseMatiereUser::with([
                'classe.anneeScolaire',
                'matiere',
            ])
            ->where('user_id', $user->id)
            ->whereIn('statut', ['actif', 'termine'])
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('classe', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
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
            ->when($annee, function ($query) use ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            })
            ->where('statut', 'actif')
            ->distinct('eleve_id')
            ->count('eleve_id');

        $nombreEvaluations = Evaluation::whereExists(function ($subQuery) use ($user) {
                $subQuery->selectRaw('1')
                    ->from('classe_matiere_users')
                    ->whereColumn('classe_matiere_users.classe_id', 'evaluations.classe_id')
                    ->whereColumn('classe_matiere_users.matiere_id', 'evaluations.matiere_id')
                    ->where('classe_matiere_users.user_id', $user->id)
                    ->whereIn('classe_matiere_users.statut', ['actif', 'termine'])
                    ->whereNull('classe_matiere_users.deleted_at');
            })
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('classe', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->count();

        $emploisDuTemps = EmploiDuTemps::with([
                'affectation.classe',
                'affectation.matiere',
            ])
            ->whereHas('affectation', function ($query) use ($user, $annee) {
                $query->where('user_id', $user->id)
                    ->whereIn('statut', ['actif', 'termine']);

                if ($annee) {
                    $query->whereHas('classe', function ($q) use ($annee) {
                        $q->where('annee_scolaire_id', $annee->id);
                    });
                }
            })
            ->whereDate('emploi_du_temps.date_debut', $debutSemaine->toDateString())
            ->whereHas('affectation', function ($query) use ($debutSemaine, $finSemaine) {
                $query->whereDate('classe_matiere_users.date_debut', '<=', $finSemaine->toDateString())
                    ->where(function ($q) use ($debutSemaine) {
                        $q->whereNull('classe_matiere_users.date_fin')
                            ->orWhereDate('classe_matiere_users.date_fin', '>=', $debutSemaine->toDateString());
                    });
            })
            ->orderByRaw($this->ordreJoursSql('emploi_du_temps.jour'))
            ->orderBy('heure_debut')
            ->get();

        $evaluationsRecentes = Evaluation::with(['classe', 'matiere', 'trimestre'])
            ->whereExists(function ($subQuery) use ($user) {
                $subQuery->selectRaw('1')
                    ->from('classe_matiere_users')
                    ->whereColumn('classe_matiere_users.classe_id', 'evaluations.classe_id')
                    ->whereColumn('classe_matiere_users.matiere_id', 'evaluations.matiere_id')
                    ->where('classe_matiere_users.user_id', $user->id)
                    ->whereIn('classe_matiere_users.statut', ['actif', 'termine'])
                    ->whereNull('classe_matiere_users.deleted_at');
            })
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('classe', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->orderByDesc('date_evaluation')
            ->limit(5)
            ->get();

        return view('dashboard.enseignant', compact(
            'annees',
            'annee',
            'selectedAnneeId',
            'affectations',
            'nombreClasses',
            'nombreMatieres',
            'nombreEleves',
            'nombreEvaluations',
            'emploisDuTemps',
            'evaluationsRecentes'
        ));
    }

    private function anneeScolaireCourante(): ?AnneeScolaire
    {
        return AnneeScolaire::where('statut', 'active')
            ->orderByDesc('date_debut')
            ->first()
            ?? AnneeScolaire::orderByDesc('date_debut')->first();
    }

    private function ordreJoursSql(string $colonne): string
    {
        return "CASE {$colonne} "
            . "WHEN 'lundi' THEN 1 "
            . "WHEN 'mardi' THEN 2 "
            . "WHEN 'mercredi' THEN 3 "
            . "WHEN 'jeudi' THEN 4 "
            . "WHEN 'vendredi' THEN 5 "
            . "WHEN 'samedi' THEN 6 "
            . "ELSE 7 END";
    }
}
