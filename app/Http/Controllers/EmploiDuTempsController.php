<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\EmploiDuTemps;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Evaluation;
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

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $classes = Classe::with('anneeScolaire')
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
            ->orderByRaw("FIELD(jour, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi')")
            ->orderBy('heure_debut')
            ->get();

        return view('emplois_du_temps.index', compact(
            'emplois',
            'annees',
            'classes',
            'enseignants',
            'selectedAnneeId',
            'selectedClasseId',
            'selectedEnseignantId'
        ));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        $affectations = ClasseMatiereUser::with([
                'classe.anneeScolaire',
                'matiere',
                'enseignant',
            ])
            ->where('statut', 'actif')
            ->orderBy('classe_id')
            ->get();

        return view('emplois_du_temps.create', compact('affectations'));
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
        ]);

        $affectation = ClasseMatiereUser::with('classe.anneeScolaire')
            ->findOrFail($validated['classe_matiere_user_id']);

        if ($affectation->classe?->anneeScolaire?->estFermee()) {
            return back()
                ->withErrors([
                    'classe_matiere_user_id' => 'Impossible de créer un créneau dans une année scolaire fermée.',
                ])
                ->withInput();
        }

        if ($this->conflitExiste(
            $affectation,
            $validated['jour'],
            $validated['heure_debut'],
            $validated['heure_fin']
        )) {
            return back()
                ->withErrors([
                    'heure_debut' => 'Conflit détecté : cette classe ou cet enseignant a déjà un cours sur ce créneau.',
                ])
                ->withInput();
        }

        EmploiDuTemps::create($validated);

        return redirect()
            ->route('emplois-du-temps.index')
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
    public function edit(EmploiDuTemps $emploi_du_temps)
    {
        $affectations = ClasseMatiereUser::with([
                'classe.anneeScolaire',
                'matiere',
                'enseignant',
            ])
            ->orderBy('classe_id')
            ->get();

        return view('emplois_du_temps.edit', compact('emploi_du_temps', 'affectations'));
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
        ]);

        $affectation = ClasseMatiereUser::with('classe.anneeScolaire')
            ->findOrFail($validated['classe_matiere_user_id']);

        if ($affectation->classe?->anneeScolaire?->estFermee()) {
            return back()
                ->withErrors([
                    'classe_matiere_user_id' => 'Impossible de déplacer ce créneau vers une année scolaire fermée.',
                ])
                ->withInput();
        }

        if ($this->conflitExiste(
            $affectation,
            $validated['jour'],
            $validated['heure_debut'],
            $validated['heure_fin'],
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
            ->route('emplois-du-temps.index')
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
        ?int $ignoreId = null
    ): bool {
        $query = EmploiDuTemps::where('jour', $jour)
            ->where('heure_debut', '<', $heureFin)
            ->where('heure_fin', '>', $heureDebut)
            ->whereHas('affectation', function ($q) use ($affectation) {
                $q->where('classe_id', $affectation->classe_id)
                    ->orWhere('user_id', $affectation->user_id);
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

        /**
     * Affiche l'emploi du temps hebdomadaire d'une classe.
     */
    public function semaineClasse(Request $request)
    {
        $user = Auth::user();

        $dateReference = $request->input('semaine')
            ? Carbon::parse($request->input('semaine'))
            : now();

        $debutSemaine = $dateReference->copy()->startOfWeek(Carbon::MONDAY);
        $finSemaine = $dateReference->copy()->endOfWeek(Carbon::SATURDAY);

        $jours = $this->joursSemaine($debutSemaine);

        $classesQuery = Classe::with('anneeScolaire')
            ->orderBy('annee_scolaire_id')
            ->orderBy('niveau')
            ->orderBy('nom');

        if ($user->estEnseignant()) {
            $classeIds = ClasseMatiereUser::where('user_id', $user->id)
                ->pluck('classe_id')
                ->unique();

            $classesQuery->whereIn('id', $classeIds);
        }

        $classes = $classesQuery->get();

        $selectedClasseId = $request->input('classe_id') ?? $classes->first()?->id;

        $classe = $selectedClasseId
            ? Classe::with('anneeScolaire')->find($selectedClasseId)
            : null;

        $emplois = collect();
        $evaluations = collect();

        if ($classe) {
            $emplois = EmploiDuTemps::with([
                    'affectation.classe.anneeScolaire',
                    'affectation.matiere',
                    'affectation.enseignant',
                ])
                ->whereHas('affectation', function ($query) use ($classe) {
                    $query->where('classe_id', $classe->id);
                })
                ->orderByRaw("FIELD(jour, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi')")
                ->orderBy('heure_debut')
                ->get();

            $evaluations = Evaluation::with(['matiere', 'createur'])
                ->where('classe_id', $classe->id)
                ->whereBetween('date_evaluation', [
                    $debutSemaine->toDateString(),
                    $finSemaine->toDateString(),
                ])
                ->get();
        }

        $planning = $this->construirePlanningAvecEvaluations($emplois, $evaluations, $jours);

        return view('emplois_du_temps.semaine_classe', compact(
            'classes',
            'classe',
            'selectedClasseId',
            'dateReference',
            'debutSemaine',
            'finSemaine',
            'jours',
            'planning'
        ));
    }

        /**
     * Affiche l'emploi du temps hebdomadaire d'un enseignant.
     */
    public function semaineEnseignant(Request $request)
    {
        $user = Auth::user();

        $dateReference = $request->input('semaine')
            ? Carbon::parse($request->input('semaine'))
            : now();

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
                ->whereHas('affectation', function ($query) use ($enseignant) {
                    $query->where('user_id', $enseignant->id);
                })
                ->orderByRaw("FIELD(jour, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi')")
                ->orderBy('heure_debut')
                ->get();

            $classeIds = $emplois
                ->pluck('affectation.classe_id')
                ->unique()
                ->filter();

            $evaluations = Evaluation::with(['matiere', 'createur'])
                ->whereIn('classe_id', $classeIds)
                ->whereBetween('date_evaluation', [
                    $debutSemaine->toDateString(),
                    $finSemaine->toDateString(),
                ])
                ->get();
        }

        $planning = $this->construirePlanningAvecEvaluations($emplois, $evaluations, $jours);

        return view('emplois_du_temps.semaine_enseignant', compact(
            'enseignants',
            'enseignant',
            'selectedEnseignantId',
            'dateReference',
            'debutSemaine',
            'finSemaine',
            'jours',
            'planning'
        ));
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
     * Construit le planning hebdomadaire.
     * Si une évaluation existe pour la même classe, la même date et la même heure,
     * elle prend la place du cours normal.
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

            $classeId = $emploi->affectation->classe_id;

            $evaluation = $evaluations->first(function ($evaluation) use (
                $classeId,
                $dateJour,
                $heureDebut,
                $heureFin
            ) {
                return (int) $evaluation->classe_id === (int) $classeId
                    && $evaluation->date_evaluation?->toDateString() === $dateJour
                    && $evaluation->heure_debut?->format('H:i') === $heureDebut
                    && $evaluation->heure_fin?->format('H:i') === $heureFin;
            });

            $planning[$jour][] = [
                'emploi' => $emploi,
                'evaluation' => $evaluation,
                'date' => $dateJour,
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
            ];
        }

        foreach ($planning as $jour => $creneaux) {
            usort($creneaux, function ($a, $b) {
                return strcmp($a['heure_debut'], $b['heure_debut']);
            });

            $planning[$jour] = $creneaux;
        }

        return $planning;
    }
}
