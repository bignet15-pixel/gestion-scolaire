<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\EmploiDuTemps;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Evaluation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EmploiDuTempsController extends Controller
{
    /**
     * Affiche la liste des créneaux d'emploi du temps.
     */
    public function index(Request $request)
    {
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $selectedClasseId = $request->input('classe_id');
        $selectedEnseignantId = $request->input('enseignant_id');
        $dateReference = $this->dateReference($request);
        $debutSemaine = $dateReference->copy()->startOfWeek(Carbon::MONDAY);
        $finSemaine = $dateReference->copy()->endOfWeek(Carbon::SATURDAY);

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $classes = Classe::with('anneeScolaire')
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->orderBy('annee_scolaire_id')
            ->orderBy('niveau')
            ->orderBy('nom')
            ->get();

        $enseignants = User::where('role', 'enseignant')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $emplois = EmploiDuTemps::with([
                'affectation.classe.anneeScolaire',
                'affectation.matiere',
                'affectation.enseignant',
            ])
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->whereHas('affectation.classe', function ($q) use ($selectedAnneeId) {
                    $q->where('annee_scolaire_id', $selectedAnneeId);
                });
            })
            ->when($selectedClasseId, function ($query) use ($selectedClasseId) {
                $query->whereHas('affectation', function ($q) use ($selectedClasseId) {
                    $q->where('classe_id', $selectedClasseId);
                });
            })
            ->when($selectedEnseignantId, function ($query) use ($selectedEnseignantId) {
                $query->whereHas('affectation', function ($q) use ($selectedEnseignantId) {
                    $q->where('user_id', $selectedEnseignantId);
                });
            })
            ->where(function ($query) use ($debutSemaine) {
                $this->appliquerSemaineExacteEmploi($query, $debutSemaine, 'emploi_du_temps');
            })
            ->whereHas('affectation', function ($query) use ($debutSemaine, $finSemaine) {
                $this->appliquerChevauchementPeriode($query, $debutSemaine, $finSemaine, 'classe_matiere_users');
            })
            ->orderByRaw($this->ordreJoursSql('emploi_du_temps.jour'))
            ->orderBy('heure_debut')
            ->get();

        return view('emplois_du_temps.index', compact(
            'emplois',
            'annees',
            'classes',
            'enseignants',
            'selectedAnneeId',
            'selectedClasseId',
            'selectedEnseignantId',
            'dateReference',
            'debutSemaine',
            'finSemaine'
        ));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create(Request $request)
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $dateReference = $this->dateReference($request);
        $debutSemaine = $dateReference->copy()->startOfWeek(Carbon::MONDAY);
        $finSemaine = $dateReference->copy()->endOfWeek(Carbon::SATURDAY);

        if (! $selectedAnneeId && $annees->isNotEmpty()) {
            $selectedAnneeId = $this->anneeScolaireCourante()?->id ?? $annees->first()->id;
        }

        $affectations = ClasseMatiereUser::with([
                'classe.anneeScolaire',
                'matiere',
                'enseignant',
            ])
            ->where('statut', 'actif')
            ->whereHas('classe.anneeScolaire', function ($query) {
                $query->where('statut', 'active');
            })
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->whereHas('classe', function ($q) use ($selectedAnneeId) {
                    $q->where('annee_scolaire_id', $selectedAnneeId);
                });
            })
            ->orderBy('classe_id')
            ->get();

        return view('emplois_du_temps.create', compact(
            'affectations',
            'annees',
            'selectedAnneeId',
            'dateReference',
            'debutSemaine',
            'finSemaine'
        ));
    }

    /**
     * Enregistre un créneau.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'classe_matiere_user_id' => ['required', 'exists:classe_matiere_users,id'],
            'jour' => ['required', 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
            'salle' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['required', 'date'],
        ]);

        $validated = $this->normaliserSemaineEmploi($validated);

        $affectation = ClasseMatiereUser::with('classe.anneeScolaire')
            ->findOrFail($validated['classe_matiere_user_id']);

        if (! $affectation->estActif()) {
            return back()
                ->withErrors([
                    'classe_matiere_user_id' => 'Impossible de créer un créneau avec une affectation non active.',
                ])
                ->withInput();
        }

        if ($affectation->classe?->anneeScolaire?->estFermee()) {
            return back()
                ->withErrors([
                    'classe_matiere_user_id' => 'Impossible de créer un créneau dans une année scolaire fermée.',
                ])
                ->withInput();
        }

        if ($message = $this->periodeEmploiInvalide($affectation, $validated['date_debut'], $validated['date_fin'] ?? null)) {
            return back()
                ->withErrors(['date_debut' => $message])
                ->withInput();
        }

        if ($this->conflitExiste(
            $affectation,
            $validated['jour'],
            $validated['heure_debut'],
            $validated['heure_fin'],
            $validated['date_debut'],
            $validated['date_fin'] ?? null
        )) {
            return back()
                ->withErrors([
                    'heure_debut' => 'Conflit détecté : cette classe ou cet enseignant a déjà un cours sur ce créneau.',
                ])
                ->withInput();
        }

        EmploiDuTemps::create($validated);

        return redirect()
            ->route('emplois-du-temps.index', [
                'annee_scolaire_id' => $affectation->classe?->annee_scolaire_id,
                'semaine' => $validated['date_debut'],
            ])
            ->with('success', 'Créneau ajouté avec succès.');
    }

    /**
     * Affiche le détail d'un créneau.
     */
    public function show(EmploiDuTemps $emploi_du_temps)
    {
        $emploi_du_temps->load([
            'affectation.classe.anneeScolaire',
            'affectation.matiere',
            'affectation.enseignant',
        ]);

        return view('emplois_du_temps.show', compact('emploi_du_temps'));
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(Request $request, EmploiDuTemps $emploi_du_temps)
    {
        if ($this->emploiEstVerrouille($emploi_du_temps)) {
            return redirect()
                ->route('emplois-du-temps.show', $emploi_du_temps)
                ->withErrors([
                    'emploi_du_temps' => 'Impossible de modifier ce créneau : son année scolaire est fermée.',
                ]);
        }

        $emploi_du_temps->loadMissing('affectation.classe.anneeScolaire');

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $selectedAnneeId = $request->input('annee_scolaire_id')
            ?? $emploi_du_temps->affectation?->classe?->annee_scolaire_id
            ?? $this->anneeScolaireCourante()?->id;
        $dateReference = $this->dateReference($request);
        $debutSemaine = $dateReference->copy()->startOfWeek(Carbon::MONDAY);
        $finSemaine = $dateReference->copy()->endOfWeek(Carbon::SATURDAY);

        $affectations = ClasseMatiereUser::with([
                'classe.anneeScolaire',
                'matiere',
                'enseignant',
            ])
            ->where('statut', 'actif')
            ->whereHas('classe.anneeScolaire', function ($query) {
                $query->where('statut', 'active');
            })
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->whereHas('classe', function ($q) use ($selectedAnneeId) {
                    $q->where('annee_scolaire_id', $selectedAnneeId);
                });
            })
            ->orderBy('classe_id')
            ->get();

        return view('emplois_du_temps.edit', compact(
            'emploi_du_temps',
            'affectations',
            'annees',
            'selectedAnneeId',
            'dateReference',
            'debutSemaine',
            'finSemaine'
        ));
    }

    /**
     * Met à jour un créneau.
     */
    public function update(Request $request, EmploiDuTemps $emploi_du_temps)
    {
        if ($this->emploiEstVerrouille($emploi_du_temps)) {
            return back()->withErrors([
                'emploi_du_temps' => 'Impossible de modifier ce créneau : son année scolaire est fermée.',
            ]);
        }

        $validated = $request->validate([
            'classe_matiere_user_id' => ['required', 'exists:classe_matiere_users,id'],
            'jour' => ['required', 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
            'salle' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['required', 'date'],
        ]);

        $validated = $this->normaliserSemaineEmploi($validated);

        $affectation = ClasseMatiereUser::with('classe.anneeScolaire')
            ->findOrFail($validated['classe_matiere_user_id']);

        if (! $affectation->estActif()) {
            return back()
                ->withErrors([
                    'classe_matiere_user_id' => 'Impossible d’utiliser une affectation non active.',
                ])
                ->withInput();
        }

        if ($affectation->classe?->anneeScolaire?->estFermee()) {
            return back()
                ->withErrors([
                    'classe_matiere_user_id' => 'Impossible de déplacer ce créneau vers une année scolaire fermée.',
                ])
                ->withInput();
        }

        if ($message = $this->periodeEmploiInvalide($affectation, $validated['date_debut'], $validated['date_fin'] ?? null)) {
            return back()
                ->withErrors(['date_debut' => $message])
                ->withInput();
        }

        if ($this->conflitExiste(
            $affectation,
            $validated['jour'],
            $validated['heure_debut'],
            $validated['heure_fin'],
            $validated['date_debut'],
            $validated['date_fin'] ?? null,
            $emploi_du_temps->id
        )) {
            return back()
                ->withErrors([
                    'heure_debut' => 'Conflit détecté : cette classe ou cet enseignant a déjà un cours sur ce créneau.',
                ])
                ->withInput();
        }

        $emploi_du_temps->update($validated);

        return redirect()
            ->route('emplois-du-temps.index', [
                'annee_scolaire_id' => $affectation->classe?->annee_scolaire_id,
                'semaine' => $validated['date_debut'],
            ])
            ->with('success', 'Créneau modifié avec succès.');
    }

    /**
     * Supprime logiquement un créneau.
     */
    public function destroy(EmploiDuTemps $emploi_du_temps)
    {
        if ($this->emploiEstVerrouille($emploi_du_temps)) {
            return back()->withErrors([
                'emploi_du_temps' => 'Impossible de supprimer ce créneau : son année scolaire est fermée.',
            ]);
        }

        $emploi_du_temps->loadMissing('affectation.classe.anneeScolaire');

        $emploi_du_temps->update([
            'is_deleted' => true,
        ]);

        $emploi_du_temps->delete();

        return redirect()
            ->route('emplois-du-temps.index')
            ->with('success', 'Créneau supprimé avec succès.');
    }

    /**
     * Vérifie si une classe ou un enseignant a déjà un créneau qui chevauche.
     */
    private function conflitExiste(
        ClasseMatiereUser $affectation,
        string $jour,
        string $heureDebut,
        string $heureFin,
        string $dateDebut,
        ?string $dateFin = null,
        ?int $ignoreId = null
    ): bool {
        $query = EmploiDuTemps::where('jour', $jour)
            ->where('heure_debut', '<', $heureFin)
            ->where('heure_fin', '>', $heureDebut)
            ->whereDate('date_debut', $dateDebut)
            ->whereHas('affectation.classe', function ($q) use ($affectation) {
                $q->where('annee_scolaire_id', $affectation->classe?->annee_scolaire_id);
            })
            ->whereHas('affectation', function ($q) use ($affectation) {
                $q->where(function ($condition) use ($affectation) {
                    $condition->where('classe_id', $affectation->classe_id)
                        ->orWhere('user_id', $affectation->user_id);
                });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private function emploiEstVerrouille(EmploiDuTemps $emploi): bool
    {
        $emploi->loadMissing('affectation.classe.anneeScolaire');

        return $emploi->affectation?->classe?->anneeScolaire?->estFermee() ?? false;
    }

    private function periodeEmploiInvalide(
        ClasseMatiereUser $affectation,
        string $dateDebut,
        ?string $dateFin = null
    ): ?string {
        $affectation->loadMissing('classe.anneeScolaire');

        $debut = Carbon::parse($dateDebut)->startOfDay();
        $fin = $dateFin
            ? Carbon::parse($dateFin)->startOfDay()
            : $debut->copy()->endOfWeek(Carbon::SATURDAY)->startOfDay();
        $annee = $affectation->classe?->anneeScolaire;

        if ($affectation->date_debut && $fin->lt($affectation->date_debut)) {
            return 'La semaine du créneau est antérieure au début de l’affectation.';
        }

        if ($affectation->date_fin && $debut->gt($affectation->date_fin)) {
            return 'La semaine du créneau est postérieure à la fin de l’affectation.';
        }

        if ($annee?->date_debut && $fin->lt($annee->date_debut)) {
            return 'La semaine du créneau doit appartenir à l’année scolaire de la classe.';
        }

        if ($annee?->date_fin && $debut->gt($annee->date_fin)) {
            return 'La semaine du créneau doit appartenir à l’année scolaire de la classe.';
        }

        return null;
    }

    private function normaliserSemaineEmploi(array $validated): array
    {
        $debutSemaine = Carbon::parse($validated['date_debut'])
            ->startOfWeek(Carbon::MONDAY);

        $validated['date_debut'] = $debutSemaine->toDateString();
        $validated['date_fin'] = $debutSemaine->copy()
            ->endOfWeek(Carbon::SATURDAY)
            ->toDateString();

        return $validated;
    }

    private function appliquerSemaineExacteEmploi($query, Carbon $debutSemaine, ?string $table = null)
    {
        $dateDebut = $table ? $table . '.date_debut' : 'date_debut';

        return $query->whereDate($dateDebut, $debutSemaine->toDateString());
    }

    private function appliquerChevauchementPeriode($query, Carbon $debutSemaine, Carbon $finSemaine, ?string $table = null)
    {
        $dateDebut = $table ? $table . '.date_debut' : 'date_debut';
        $dateFin = $table ? $table . '.date_fin' : 'date_fin';

        return $query
            ->whereDate($dateDebut, '<=', $finSemaine->toDateString())
            ->where(function ($q) use ($dateFin, $debutSemaine) {
                $q->whereNull($dateFin)
                    ->orWhereDate($dateFin, '>=', $debutSemaine->toDateString());
            });
    }

    /**
     * Affiche l'emploi du temps hebdomadaire d'une classe.
     */
    public function semaineClasse(Request $request)
    {
        return view('emplois_du_temps.semaine_classe', $this->donneesSemaineClasse($request));
    }

    /**
     * Génère le PDF du planning hebdomadaire d'une classe.
     */
    public function imprimerSemaineClasse(Request $request)
    {
        $data = $this->donneesSemaineClasse($request);

        $nomClasse = $data['classe']?->nom ?? 'classe';
        $nomFichier = 'planning-classe-' . str_replace(' ', '-', strtolower($nomClasse)) . '.pdf';

        return Pdf::loadView('pdf.planning_classe', $data)
            ->setPaper('a4', 'landscape')
            ->download($nomFichier);
    }

    /**
     * Affiche l'emploi du temps hebdomadaire d'un enseignant.
     */
    public function semaineEnseignant(Request $request)
    {
        return view('emplois_du_temps.semaine_enseignant', $this->donneesSemaineEnseignant($request));
    }

    /**
     * Génère le PDF du planning hebdomadaire d'un enseignant.
     */
    public function imprimerSemaineEnseignant(Request $request)
    {
        $data = $this->donneesSemaineEnseignant($request);

        $nomEnseignant = $data['enseignant']?->name ?? 'enseignant';
        $nomFichier = 'planning-enseignant-' . str_replace(' ', '-', strtolower($nomEnseignant)) . '.pdf';

        return Pdf::loadView('pdf.planning_enseignant', $data)
            ->setPaper('a4', 'landscape')
            ->download($nomFichier);
    }

    /**
     * Prépare les données du planning classe pour la vue et le PDF.
     */
    private function donneesSemaineClasse(Request $request): array
    {
        $user = Auth::user();
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        if (! $selectedAnneeId && $annees->isNotEmpty()) {
            $selectedAnneeId = $this->anneeScolaireCourante()?->id ?? $annees->first()->id;
        }

        $dateReference = $this->dateReference($request);

        $debutSemaine = $dateReference->copy()->startOfWeek(Carbon::MONDAY);
        $finSemaine = $dateReference->copy()->endOfWeek(Carbon::SATURDAY);

        $jours = $this->joursSemaine($debutSemaine);

        $classesQuery = Classe::with('anneeScolaire')
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->orderBy('annee_scolaire_id')
            ->orderBy('niveau')
            ->orderBy('nom');

        if ($user->estEnseignant()) {
            $classeIds = ClasseMatiereUser::where('user_id', $user->id)
                ->whereIn('statut', ['actif', 'termine'])
                ->where(function ($query) use ($debutSemaine, $finSemaine) {
                    $this->appliquerChevauchementPeriode($query, $debutSemaine, $finSemaine, 'classe_matiere_users');
                })
                ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                    $query->whereHas('classe', function ($q) use ($selectedAnneeId) {
                        $q->where('annee_scolaire_id', $selectedAnneeId);
                    });
                })
                ->pluck('classe_id')
                ->unique();

            $classesQuery->whereIn('id', $classeIds);
        }

        $classes = $classesQuery->get();

        $selectedClasseId = $request->input('classe_id') ?? $classes->first()?->id;

        $classe = $selectedClasseId
            ? $classes->first(fn ($classeOption) => (string) $classeOption->id === (string) $selectedClasseId)
            : null;

        $emplois = collect();
        $evaluations = collect();

        if ($classe) {
            if ($user->estEnseignant()) {
                $autorise = ClasseMatiereUser::where('user_id', $user->id)
                    ->where('classe_id', $classe->id)
                    ->whereIn('statut', ['actif', 'termine'])
                    ->where(function ($query) use ($debutSemaine, $finSemaine) {
                        $this->appliquerChevauchementPeriode($query, $debutSemaine, $finSemaine, 'classe_matiere_users');
                    })
                    ->exists();

                if (! $autorise) {
                    abort(403, 'Accès refusé.');
                }
            }

            $emplois = EmploiDuTemps::with([
                    'affectation.classe.anneeScolaire',
                    'affectation.matiere',
                    'affectation.enseignant',
                ])
                ->whereHas('affectation', function ($query) use ($classe) {
                    $query->where('classe_id', $classe->id)
                        ->whereIn('statut', ['actif', 'termine']);
                })
                ->where(function ($query) use ($debutSemaine) {
                    $this->appliquerSemaineExacteEmploi($query, $debutSemaine, 'emploi_du_temps');
                })
                ->whereHas('affectation', function ($query) use ($debutSemaine, $finSemaine) {
                    $this->appliquerChevauchementPeriode($query, $debutSemaine, $finSemaine, 'classe_matiere_users');
                })
                ->orderByRaw($this->ordreJoursSql('emploi_du_temps.jour'))
                ->orderBy('heure_debut')
                ->get();

            $evaluations = Evaluation::with(['classe', 'matiere', 'createur'])
                ->where('classe_id', $classe->id)
                ->whereBetween('date_evaluation', [
                    $debutSemaine->toDateString(),
                    $finSemaine->toDateString(),
                ])
                ->get();
        }

        $planning = $this->construirePlanningAvecEvaluations($emplois, $evaluations, $jours);
        $creneauxHoraires = $this->creneauxHorairesPlanning();

        return [
            'annees' => $annees,
            'classes' => $classes,
            'classe' => $classe,
            'selectedAnneeId' => $selectedAnneeId,
            'selectedClasseId' => $selectedClasseId,
            'dateReference' => $dateReference,
            'debutSemaine' => $debutSemaine,
            'finSemaine' => $finSemaine,
            'jours' => $jours,
            'planning' => $planning,
            'creneauxHoraires' => $creneauxHoraires,
            'planningGrille' => $this->construireGrilleHoraire($planning, $creneauxHoraires),
            'detailsPlanning' => $this->detailsPlanning($planning),
        ];
    }

    /**
     * Prépare les données du planning enseignant pour la vue et le PDF.
     */
    private function donneesSemaineEnseignant(Request $request): array
    {
        $user = Auth::user();
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        if (! $selectedAnneeId && $annees->isNotEmpty()) {
            $selectedAnneeId = $this->anneeScolaireCourante()?->id ?? $annees->first()->id;
        }

        $dateReference = $this->dateReference($request);

        $debutSemaine = $dateReference->copy()->startOfWeek(Carbon::MONDAY);
        $finSemaine = $dateReference->copy()->endOfWeek(Carbon::SATURDAY);

        $jours = $this->joursSemaine($debutSemaine);

        $enseignants = User::where('role', 'enseignant')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        if ($user->estEnseignant()) {
            $selectedEnseignantId = $user->id;
        } else {
            $selectedEnseignantId = $request->input('enseignant_id') ?? $enseignants->first()?->id;
        }

        $enseignant = $selectedEnseignantId
            ? User::find($selectedEnseignantId)
            : null;

        $emplois = collect();
        $evaluations = collect();

        if ($enseignant) {
            $emplois = EmploiDuTemps::with([
                    'affectation.classe.anneeScolaire',
                    'affectation.matiere',
                    'affectation.enseignant',
                ])
                ->whereHas('affectation', function ($query) use ($enseignant, $selectedAnneeId) {
                    $query->where('user_id', $enseignant->id)
                        ->whereIn('statut', ['actif', 'termine']);

                    if ($selectedAnneeId) {
                        $query->whereHas('classe', function ($q) use ($selectedAnneeId) {
                            $q->where('annee_scolaire_id', $selectedAnneeId);
                        });
                    }
                })
                ->where(function ($query) use ($debutSemaine) {
                    $this->appliquerSemaineExacteEmploi($query, $debutSemaine, 'emploi_du_temps');
                })
                ->whereHas('affectation', function ($query) use ($debutSemaine, $finSemaine) {
                    $this->appliquerChevauchementPeriode($query, $debutSemaine, $finSemaine, 'classe_matiere_users');
                })
                ->orderByRaw($this->ordreJoursSql('emploi_du_temps.jour'))
                ->orderBy('heure_debut')
                ->get();

            $evaluations = Evaluation::with(['classe', 'matiere', 'createur'])
                ->whereExists(function ($subQuery) use ($enseignant) {
                    $subQuery->selectRaw('1')
                        ->from('classe_matiere_users')
                        ->whereColumn('classe_matiere_users.classe_id', 'evaluations.classe_id')
                        ->whereColumn('classe_matiere_users.matiere_id', 'evaluations.matiere_id')
                        ->where('classe_matiere_users.user_id', $enseignant->id)
                        ->whereIn('classe_matiere_users.statut', ['actif', 'termine'])
                        ->whereNull('classe_matiere_users.deleted_at')
                        ->whereColumn('classe_matiere_users.date_debut', '<=', 'evaluations.date_evaluation')
                        ->where(function ($dateQuery) {
                            $dateQuery->whereNull('classe_matiere_users.date_fin')
                                ->orWhereColumn('classe_matiere_users.date_fin', '>=', 'evaluations.date_evaluation');
                        });
                })
                ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                    $query->whereHas('classe', function ($q) use ($selectedAnneeId) {
                        $q->where('annee_scolaire_id', $selectedAnneeId);
                    });
                })
                ->whereBetween('date_evaluation', [
                    $debutSemaine->toDateString(),
                    $finSemaine->toDateString(),
                ])
                ->get();
        }

        $planning = $this->construirePlanningAvecEvaluations($emplois, $evaluations, $jours);
        $creneauxHoraires = $this->creneauxHorairesPlanning();

        return [
            'annees' => $annees,
            'enseignants' => $enseignants,
            'enseignant' => $enseignant,
            'selectedAnneeId' => $selectedAnneeId,
            'selectedEnseignantId' => $selectedEnseignantId,
            'dateReference' => $dateReference,
            'debutSemaine' => $debutSemaine,
            'finSemaine' => $finSemaine,
            'jours' => $jours,
            'planning' => $planning,
            'creneauxHoraires' => $creneauxHoraires,
            'planningGrille' => $this->construireGrilleHoraire($planning, $creneauxHoraires),
            'detailsPlanning' => $this->detailsPlanning($planning),
        ];
    }

    /**
     * Retourne les jours de la semaine avec leurs dates réelles.
     */
    private function joursSemaine(Carbon $debutSemaine): array
    {
        return [
            'lundi' => $debutSemaine->copy(),
            'mardi' => $debutSemaine->copy()->addDay(),
            'mercredi' => $debutSemaine->copy()->addDays(2),
            'jeudi' => $debutSemaine->copy()->addDays(3),
            'vendredi' => $debutSemaine->copy()->addDays(4),
            'samedi' => $debutSemaine->copy()->addDays(5),
        ];
    }

    /**
     * Définit les lignes horaires utilisées par les plannings.
     */
    private function creneauxHorairesPlanning(): array
    {
        return [
            ['id' => '07_08', 'debut' => '07:00', 'fin' => '08:00', 'label' => '07h00 - 08h00', 'type' => 'cours'],
            ['id' => '08_09', 'debut' => '08:00', 'fin' => '09:00', 'label' => '08h00 - 09h00', 'type' => 'cours'],
            ['id' => '09_10', 'debut' => '09:00', 'fin' => '10:00', 'label' => '09h00 - 10h00', 'type' => 'cours'],
            ['id' => 'pause_10_1030', 'debut' => '10:00', 'fin' => '10:30', 'label' => '10h00 - 10h30', 'type' => 'pause', 'texte' => 'Pause'],
            ['id' => '1030_1130', 'debut' => '10:30', 'fin' => '11:30', 'label' => '10h30 - 11h30', 'type' => 'cours'],
            ['id' => '1130_12', 'debut' => '11:30', 'fin' => '12:00', 'label' => '11h30 - 12h00', 'type' => 'cours'],
            ['id' => '12_13', 'debut' => '12:00', 'fin' => '13:00', 'label' => '12h00 - 13h00', 'type' => 'vide', 'texte' => ''],
            ['id' => '13_14', 'debut' => '13:00', 'fin' => '14:00', 'label' => '13h00 - 14h00', 'type' => 'cours'],
            ['id' => '14_15', 'debut' => '14:00', 'fin' => '15:00', 'label' => '14h00 - 15h00', 'type' => 'cours'],
            ['id' => '15_16', 'debut' => '15:00', 'fin' => '16:00', 'label' => '15h00 - 16h00', 'type' => 'cours'],
            ['id' => '16_17', 'debut' => '16:00', 'fin' => '17:00', 'label' => '16h00 - 17h00', 'type' => 'cours'],
            ['id' => '17_18', 'debut' => '17:00', 'fin' => '18:00', 'label' => '17h00 - 18h00', 'type' => 'cours'],
        ];
    }

    /**
     * Range les cours dans les cellules jour + heure du planning.
     */
    private function construireGrilleHoraire(array $planning, array $creneauxHoraires): array
    {
        $grille = [];

        foreach ($creneauxHoraires as $creneau) {
            if ($creneau['type'] !== 'cours') {
                continue;
            }

            foreach (array_keys($planning) as $jour) {
                $grille[$creneau['id']][$jour] = [];
            }
        }

        foreach ($planning as $jour => $items) {
            foreach ($items as $item) {
                $debutCours = $this->heureEnMinutes($item['heure_debut']);
                $finCours = $this->heureEnMinutes($item['heure_fin']);

                foreach ($creneauxHoraires as $creneau) {
                    if ($creneau['type'] !== 'cours') {
                        continue;
                    }

                    $debutCreneau = $this->heureEnMinutes($creneau['debut']);
                    $finCreneau = $this->heureEnMinutes($creneau['fin']);

                    $chevauche = $debutCours < $finCreneau && $finCours > $debutCreneau;

                    if ($chevauche) {
                        $grille[$creneau['id']][$jour][] = $item;
                    }
                }
            }
        }

        foreach ($grille as $creneauId => $jours) {
            foreach ($jours as $jour => $items) {
                $evaluations = collect($items)->filter(fn ($item) => $item['evaluation']);

                if ($evaluations->isNotEmpty()) {
                    $grille[$creneauId][$jour] = $evaluations->values()->all();
                }
            }
        }

        return $grille;
    }

    private function heureEnMinutes(string $heure): int
    {
        [$heures, $minutes] = array_map('intval', explode(':', $heure));

        return ($heures * 60) + $minutes;
    }

    /**
     * Prépare le tableau récapitulatif des enseignants, salles et coefficients.
     */
    private function detailsPlanning(array $planning)
    {
        return collect($planning)
            ->flatMap(fn ($items) => $items)
            ->filter(fn ($item) => $item['emploi'])
            ->map(function ($item) {
                $affectation = $item['emploi']->affectation;

                return [
                    'classe' => $affectation?->classe?->nom ?? '-',
                    'matiere' => $affectation?->matiere?->nom ?? '-',
                    'enseignant' => $affectation?->enseignant?->name ?? '-',
                    'salle' => $item['emploi']->salle ?? '-',
                    'coefficient' => $affectation?->coefficient,
                ];
            })
            ->unique(function ($detail) {
                return implode('|', [
                    $detail['classe'],
                    $detail['matiere'],
                    $detail['enseignant'],
                    $detail['salle'],
                    $detail['coefficient'],
                ]);
            })
            ->values();
    }

    /**
     * Construit le planning hebdomadaire avec les cours et les évaluations programmées.
     */
    private function construirePlanningAvecEvaluations($emplois, $evaluations, array $jours)
    {
        $planning = [];

        foreach ($jours as $nomJour => $dateJour) {
            $planning[$nomJour] = [];
        }

        foreach ($emplois as $emploi) {
            $jour = $emploi->jour;

            if (! isset($jours[$jour])) {
                continue;
            }

            $dateJour = $jours[$jour]->toDateString();

            $heureDebut = $emploi->heure_debut->format('H:i');
            $heureFin = $emploi->heure_fin->format('H:i');

            $planning[$jour][] = [
                'emploi' => $emploi,
                'evaluation' => null,
                'date' => $dateJour,
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
            ];
        }

        foreach ($evaluations as $evaluation) {
            if (! $evaluation->date_evaluation || ! $evaluation->heure_debut || ! $evaluation->heure_fin) {
                continue;
            }

            $dateEvaluation = $evaluation->date_evaluation->toDateString();

            $jourEvaluation = collect($jours)->search(function ($dateJour) use ($dateEvaluation) {
                return $dateJour->toDateString() === $dateEvaluation;
            });

            if (! $jourEvaluation) {
                continue;
            }

            $planning[$jourEvaluation][] = [
                'emploi' => null,
                'evaluation' => $evaluation,
                'date' => $dateEvaluation,
                'heure_debut' => $evaluation->heure_debut->format('H:i'),
                'heure_fin' => $evaluation->heure_fin->format('H:i'),
            ];
        }

        foreach ($planning as $jour => $creneaux) {
            usort($creneaux, function ($a, $b) {
                $heure = strcmp($a['heure_debut'], $b['heure_debut']);

                if ($heure !== 0) {
                    return $heure;
                }

                return (int) ! $a['evaluation'] <=> (int) ! $b['evaluation'];
            });

            $planning[$jour] = $creneaux;
        }

        return $planning;
    }

    private function anneeScolaireCourante(): ?AnneeScolaire
    {
        return AnneeScolaire::where('statut', 'active')
            ->orderByDesc('date_debut')
            ->first()
            ?? AnneeScolaire::orderByDesc('date_debut')->first();
    }

    private function dateReference(Request $request): Carbon
    {
        $semaine = $request->input('semaine');

        if (! is_string($semaine) || trim($semaine) === '') {
            return Carbon::now();
        }

        try {
            return Carbon::parse($semaine);
        } catch (\Throwable) {
            return Carbon::now();
        }
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
