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
use App\Models\Annonce;
use App\Models\JustificationAbsenceRetard;
use App\Models\Note;
use App\Models\NotificationUtilisateur;
use App\Models\Trimestre;
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

        $trimestreActif = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)
                ->where('statut', 'actif')
                ->orderBy('date_debut')
                ->first()
            : null;

        $filtreAnneeInscription = function ($query) use ($annee) {
            if ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            }
        };

        $filtreAnneeClasse = function ($query) use ($annee) {
            if ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            }
        };

        $nombreEleves = Inscription::when($annee, $filtreAnneeInscription)
            ->where('statut', 'actif')
            ->distinct('eleve_id')
            ->count('eleve_id');

        $nombreClasses = Classe::when($annee, $filtreAnneeClasse)->count();

        $enseignantPrincipalIds = Classe::when($annee, $filtreAnneeClasse)
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

        $nombreParents = User::where('role', 'parent')
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('enfants.inscriptions', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id)
                        ->where('statut', 'actif');
                });
            })
            ->count();

        $nombreInscriptions = Inscription::when($annee, $filtreAnneeInscription)->count();

        $nombreEvaluations = Evaluation::when($annee, function ($query) use ($annee) {
                $query->whereHas('classe', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->count();

        $inscriptionsFinance = Inscription::withSum('paiements as total_paye', 'montant')
            ->when($annee, $filtreAnneeInscription)
            ->get()
            ->map(function ($inscription) {
                $fraisAttendu = (float) $inscription->frais_attendu;
                $totalPaye = (float) ($inscription->total_paye ?? 0);
                $reste = max(0, $fraisAttendu - $totalPaye);

                $inscription->total_paye_calcule = $totalPaye;
                $inscription->reste_calcule = $reste;

                return $inscription;
            });

        $totalFraisAttendus = $inscriptionsFinance->sum('frais_attendu');
        $totalFraisCollectes = $inscriptionsFinance->sum('total_paye_calcule');
        $totalRestant = $inscriptionsFinance->sum('reste_calcule');

        $nombreImpayes = $inscriptionsFinance
            ->filter(fn ($inscription) => $inscription->reste_calcule > 0)
            ->count();

        $nombreSoldes = $inscriptionsFinance
            ->filter(fn ($inscription) => $inscription->frais_attendu > 0 && $inscription->reste_calcule <= 0)
            ->count();

        $tauxRecouvrement = $totalFraisAttendus > 0
            ? round(($totalFraisCollectes / $totalFraisAttendus) * 100, 2)
            : 0;

        $classes = Classe::with(['anneeScolaire', 'enseignantPrincipal'])
            ->withCount(['inscriptions' => function ($query) {
                $query->where('statut', 'actif');
            }])
            ->when($annee, $filtreAnneeClasse)
            ->orderBy('niveau')
            ->orderBy('nom')
            ->limit(8)
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

        $paiementsDeclaresEnAttente = PaiementDeclare::where('statut', PaiementDeclare::STATUT_EN_ATTENTE)
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('inscription', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->count();

        $justificationsEnAttente = JustificationAbsenceRetard::where('statut', JustificationAbsenceRetard::STATUT_EN_ATTENTE)
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('absenceRetard.inscription', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->count();

        $reinscriptionsEnAttente = DemandeReinscription::where('statut', DemandeReinscription::STATUT_EN_ATTENTE)
            ->when($annee, function ($query) use ($annee) {
                $query->where(function ($q) use ($annee) {
                    $q->where('nouvelle_annee_scolaire_id', $annee->id)
                        ->orWhereHas('ancienneInscription', function ($sq) use ($annee) {
                            $sq->where('annee_scolaire_id', $annee->id);
                        });
                });
            })
            ->count();

        $totalDemandesEnAttente = $paiementsDeclaresEnAttente + $justificationsEnAttente + $reinscriptionsEnAttente;

        $debutSemaine = now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $absencesSemaine = AbsenceRetard::where('type', 'absence')
            ->whereDate('date_debut', '>=', $debutSemaine)
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('inscription', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->count();

        $retardsAujourdhui = AbsenceRetard::where('type', 'retard')
            ->whereDate('date_debut', now()->toDateString())
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('inscription', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->count();

        $sanctionsRecentes = SanctionAppliquee::whereIn('statut', ['appliquee', 'terminee'])
            ->whereDate('created_at', '>=', now()->subDays(7)->toDateString())
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('inscription', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->count();

        $notesPourAlertes = Note::with(['evaluation:id,bareme,trimestre_id,classe_id'])
            ->whereHas('evaluation', function ($query) use ($annee, $trimestreActif) {
                if ($annee) {
                    $query->whereHas('classe', function ($q) use ($annee) {
                        $q->where('annee_scolaire_id', $annee->id);
                    });
                }

                if ($trimestreActif) {
                    $query->where('trimestre_id', $trimestreActif->id);
                }
            })
            ->get();

        $notesFaibles = $notesPourAlertes
            ->filter(function ($note) {
                $bareme = (float) ($note->evaluation?->bareme ?? 0);

                return $bareme > 0 && ((float) $note->valeur / $bareme) < 0.5;
            })
            ->count();

        $dernieresAbsencesRetards = AbsenceRetard::with(['inscription.eleve', 'inscription.classe'])
            ->when($annee, function ($query) use ($annee) {
                $query->whereHas('inscription', function ($q) use ($annee) {
                    $q->where('annee_scolaire_id', $annee->id);
                });
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $dernieresNotesFaibles = Note::with([
                'inscription.eleve',
                'inscription.classe',
                'evaluation.matiere',
                'evaluation.trimestre',
            ])
            ->whereHas('evaluation', function ($query) use ($annee, $trimestreActif) {
                if ($annee) {
                    $query->whereHas('classe', function ($q) use ($annee) {
                        $q->where('annee_scolaire_id', $annee->id);
                    });
                }

                if ($trimestreActif) {
                    $query->where('trimestre_id', $trimestreActif->id);
                }
            })
            ->orderByDesc('created_at')
            ->limit(25)
            ->get()
            ->filter(function ($note) {
                $bareme = (float) ($note->evaluation?->bareme ?? 0);

                return $bareme > 0 && ((float) $note->valeur / $bareme) < 0.5;
            })
            ->take(5);

        $annoncesActives = Annonce::where('est_publiee', true)
            ->where(function ($query) {
                $query->whereNull('date_expiration')
                    ->orWhere('date_expiration', '>=', now());
            })
            ->count();

        $dernieresAnnonces = Annonce::with(['auteur', 'classe'])
            ->where('est_publiee', true)
            ->orderByDesc('date_publication')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $notificationsNonLues = NotificationUtilisateur::where('user_id', Auth::id())
            ->where('lue', false)
            ->count();

        $dernieresNotifications = NotificationUtilisateur::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        $ecoleInfos = [
            'nom' => 'Bangre Zaaka',
            'contact' => 'contact@gmail.com | +226 25000000',
            'devise' => 'Objectif Courage Concentration Réussite',
            'description' => 'Informations utilisées dans le tableau de bord, les reçus et les documents scolaires.',
        ];

        return view('dashboard.gestionnaire', compact(
            'annees',
            'annee',
            'trimestreActif',
            'selectedAnneeId',
            'nombreEleves',
            'nombreClasses',
            'nombreEnseignants',
            'nombreParents',
            'nombreInscriptions',
            'nombreEvaluations',
            'totalFraisAttendus',
            'totalFraisCollectes',
            'totalRestant',
            'nombreImpayes',
            'nombreSoldes',
            'tauxRecouvrement',
            'classes',
            'derniersPaiements',
            'paiementsDeclaresEnAttente',
            'justificationsEnAttente',
            'reinscriptionsEnAttente',
            'totalDemandesEnAttente',
            'absencesSemaine',
            'retardsAujourdhui',
            'sanctionsRecentes',
            'notesFaibles',
            'dernieresAbsencesRetards',
            'dernieresNotesFaibles',
            'annoncesActives',
            'dernieresAnnonces',
            'notificationsNonLues',
            'dernieresNotifications',
            'ecoleInfos'
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

        $trimestreActif = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)
                ->where('statut', 'actif')
                ->orderBy('date_debut')
                ->first()
            : null;

        $aujourdhui = now();
        $debutSemaine = $aujourdhui->copy()->startOfWeek(Carbon::MONDAY);
        $jourCourant = $this->nomJourCourant();

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
            ->orderByDesc('statut')
            ->orderBy('date_debut')
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

        $evaluationBaseQuery = Evaluation::whereExists(function ($subQuery) use ($user) {
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
            });

        $evaluationIds = (clone $evaluationBaseQuery)->pluck('id');

        $nombreEvaluations = $evaluationIds->count();

        $nombreNotesSaisies = Note::whereIn('evaluation_id', $evaluationIds)->count();

        $inscriptionsParClasse = Inscription::whereIn('classe_id', $classeIds)
            ->when($annee, function ($query) use ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            })
            ->where('statut', 'actif')
            ->selectRaw('classe_id, COUNT(*) as total')
            ->groupBy('classe_id')
            ->pluck('total', 'classe_id');

        $evaluationsRecentes = Evaluation::with(['classe', 'matiere', 'trimestre'])
            ->withCount('notes')
            ->whereIn('id', $evaluationIds)
            ->orderByDesc('date_evaluation')
            ->limit(6)
            ->get();

        $evaluationsACompleter = Evaluation::with(['classe', 'matiere', 'trimestre'])
            ->withCount('notes')
            ->whereIn('id', $evaluationIds)
            ->orderByDesc('date_evaluation')
            ->get()
            ->map(function ($evaluation) use ($inscriptionsParClasse) {
                $evaluation->total_eleves_attendus = (int) ($inscriptionsParClasse[$evaluation->classe_id] ?? 0);

                return $evaluation;
            })
            ->filter(function ($evaluation) {
                return $evaluation->total_eleves_attendus > 0
                    && $evaluation->notes_count < $evaluation->total_eleves_attendus;
            })
            ->take(5)
            ->values();

        $notesFaiblesRecentes = Note::with([
                'inscription.eleve',
                'inscription.classe',
                'evaluation.matiere',
                'evaluation.trimestre',
            ])
            ->whereIn('evaluation_id', $evaluationIds)
            ->orderByDesc('created_at')
            ->limit(40)
            ->get()
            ->filter(function ($note) {
                $bareme = (float) ($note->evaluation?->bareme ?? 0);

                return $bareme > 0 && ((float) $note->valeur / $bareme) < 0.5;
            })
            ->take(5)
            ->values();

        $nombreNotesFaibles = $notesFaiblesRecentes->count();

        $emploisDuTempsBase = EmploiDuTemps::with([
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
            ->where(function ($query) use ($aujourdhui) {
                $query->whereNull('date_debut')
                    ->orWhereDate('date_debut', '<=', $aujourdhui->toDateString());
            })
            ->where(function ($query) use ($aujourdhui) {
                $query->whereNull('date_fin')
                    ->orWhereDate('date_fin', '>=', $aujourdhui->toDateString());
            });

        $coursAujourdhui = (clone $emploisDuTempsBase)
            ->where('jour', $jourCourant)
            ->orderBy('heure_debut')
            ->limit(6)
            ->get();

        $prochainsCours = (clone $emploisDuTempsBase)
            ->orderByRaw($this->ordreJoursSql('emploi_du_temps.jour'))
            ->orderBy('heure_debut')
            ->limit(8)
            ->get();

        $absencesSemaine = AbsenceRetard::where('enregistre_par', $user->id)
            ->where('type', 'absence')
            ->whereDate('created_at', '>=', $debutSemaine->toDateString())
            ->count();

        $retardsAujourdhui = AbsenceRetard::where('enregistre_par', $user->id)
            ->where('type', 'retard')
            ->whereDate('created_at', $aujourdhui->toDateString())
            ->count();

        $dernieresAbsencesRetards = AbsenceRetard::with(['inscription.eleve', 'inscription.classe'])
            ->where('enregistre_par', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $annoncesActives = Annonce::where('est_publiee', true)
            ->where(function ($query) {
                $query->whereNull('date_expiration')
                    ->orWhere('date_expiration', '>=', now());
            })
            ->where(function ($query) use ($classeIds) {
                $query->whereIn('cible', ['tous', 'enseignants'])
                    ->orWhere(function ($q) use ($classeIds) {
                        $q->where('cible', 'classe')
                            ->whereIn('classe_id', $classeIds);
                    });
            })
            ->count();

        $dernieresAnnonces = Annonce::with(['auteur', 'classe'])
            ->where('est_publiee', true)
            ->where(function ($query) {
                $query->whereNull('date_expiration')
                    ->orWhere('date_expiration', '>=', now());
            })
            ->where(function ($query) use ($classeIds) {
                $query->whereIn('cible', ['tous', 'enseignants'])
                    ->orWhere(function ($q) use ($classeIds) {
                        $q->where('cible', 'classe')
                            ->whereIn('classe_id', $classeIds);
                    });
            })
            ->orderByDesc('date_publication')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $notificationsNonLues = NotificationUtilisateur::where('user_id', $user->id)
            ->where('lue', false)
            ->count();

        $dernieresNotifications = NotificationUtilisateur::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        return view('dashboard.enseignant', compact(
            'annees',
            'annee',
            'trimestreActif',
            'selectedAnneeId',
            'affectations',
            'nombreClasses',
            'nombreMatieres',
            'nombreEleves',
            'nombreEvaluations',
            'nombreNotesSaisies',
            'coursAujourdhui',
            'prochainsCours',
            'evaluationsRecentes',
            'evaluationsACompleter',
            'notesFaiblesRecentes',
            'nombreNotesFaibles',
            'absencesSemaine',
            'retardsAujourdhui',
            'dernieresAbsencesRetards',
            'annoncesActives',
            'dernieresAnnonces',
            'notificationsNonLues',
            'dernieresNotifications'
        ));
    }

    private function anneeScolaireCourante(): ?AnneeScolaire
    {
        return AnneeScolaire::where('statut', 'active')
            ->orderByDesc('date_debut')
            ->first()
            ?? AnneeScolaire::orderByDesc('date_debut')->first();
    }

    private function nomJourCourant(): string
    {
        return match ((int) now()->dayOfWeekIso) {
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            default => 'dimanche',
        };
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
