<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\Trimestre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\AnneeScolaire;
use App\Models\Inscription;



class EvaluationController extends Controller
{
    /**
     * Affiche la liste des évaluations avec filtres année / classe / trimestre.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $selectedAnneeId = $request->input('annee_scolaire_id');
        $selectedClasseId = $request->input('classe_id');
        $selectedTrimestreId = $request->input('trimestre_id');

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        /*
        |--------------------------------------------------------------------------
        | Classes disponibles
        |--------------------------------------------------------------------------
        |
        | Si une année est choisie, on affiche seulement les classes de cette année.
        | Si l'utilisateur est enseignant, on limite aux classes où il intervient.
        |
        */

        $classesQuery = Classe::with('anneeScolaire')
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->orderBy('niveau')
            ->orderBy('nom');

        if ($user->estEnseignant()) {
            $classeIds = ClasseMatiereUser::where('user_id', $user->id)
                ->where('statut', 'actif')
                ->pluck('classe_id')
                ->unique();

            $classesQuery->whereIn('id', $classeIds);
        }

        $classes = $classesQuery->get();

        /*
        |--------------------------------------------------------------------------
        | Trimestres disponibles
        |--------------------------------------------------------------------------
        |
        | Si une année est choisie, on affiche seulement les trimestres de cette année.
        | Sinon, on affiche tous les trimestres.
        |
        */

        $trimestres = Trimestre::with('anneeScolaire')
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->orderBy('annee_scolaire_id')
            ->orderBy('date_debut')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Requête des évaluations
        |--------------------------------------------------------------------------
        */

        $query = Evaluation::with([
                'classe.anneeScolaire',
                'matiere',
                'trimestre',
                'createur',
            ])
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->whereHas('classe', function ($q) use ($selectedAnneeId) {
                    $q->where('annee_scolaire_id', $selectedAnneeId);
                });
            })
            ->when($selectedClasseId, function ($query) use ($selectedClasseId) {
                $query->where('classe_id', $selectedClasseId);
            })
            ->when($selectedTrimestreId, function ($query) use ($selectedTrimestreId) {
                $query->where('trimestre_id', $selectedTrimestreId);
            });

        /*
        |--------------------------------------------------------------------------
        | Restriction enseignant
        |--------------------------------------------------------------------------
        |
        | L'enseignant ne voit que les évaluations des couples classe + matière
        | où il est affecté.
        |
        */

        if ($user->estEnseignant()) {
            $affectations = ClasseMatiereUser::where('user_id', $user->id)
                ->where('statut', 'actif')
                ->get(['classe_id', 'matiere_id']);

            $query->where(function ($q) use ($affectations) {
                foreach ($affectations as $affectation) {
                    $q->orWhere(function ($subQuery) use ($affectation) {
                        $subQuery->where('classe_id', $affectation->classe_id)
                            ->where('matiere_id', $affectation->matiere_id);
                    });
                }
            });
        }

        $evaluations = $query
            ->orderByDesc('date_evaluation')
            ->orderByDesc('created_at')
            ->get();

        return view('evaluations.index', compact(
            'evaluations',
            'annees',
            'classes',
            'trimestres',
            'selectedAnneeId',
            'selectedClasseId',
            'selectedTrimestreId'
        ));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        $user = Auth::user();

        if ($user->estGestionnaire()) {
            $classes = Classe::with('anneeScolaire')
                ->orderBy('annee_scolaire_id')
                ->orderBy('niveau')
                ->orderBy('nom')
                ->get();

            $matieres = Matiere::orderBy('nom')->get();

            $types = ['composition', 'test'];
        } else {
            $affectations = ClasseMatiereUser::with([
                    'classe.anneeScolaire',
                    'matiere',
                ])
                ->where('user_id', $user->id)
                ->where('statut', 'actif')
                ->get();

            $classes = $affectations
                ->pluck('classe')
                ->unique('id')
                ->values();

            $matieres = $affectations
                ->pluck('matiere')
                ->unique('id')
                ->values();

            $types = ['devoir', 'interrogation'];
        }

        $trimestres = Trimestre::with('anneeScolaire')
            ->orderByDesc('date_debut')
            ->get();

        return view('evaluations.create', compact(
            'classes',
            'matieres',
            'trimestres',
            'types'
        ));
    }

    /**
     * Enregistre une évaluation.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $typesAutorises = $user->estGestionnaire()
            ? ['composition', 'test']
            : ['devoir', 'interrogation'];

        $validated = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
            'trimestre_id' => ['required', 'exists:trimestres,id'],
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($typesAutorises)],
            'date_evaluation' => ['required', 'date'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:20'],
            'bareme' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        $classe = Classe::findOrFail($validated['classe_id']);
        $trimestre = Trimestre::with('anneeScolaire')->findOrFail($validated['trimestre_id']);

        if ($classe->anneeScolaire?->estFermee() || $trimestre->anneeScolaire?->estFermee()) {
            return back()
                ->withErrors([
                    'classe_id' => 'Impossible de créer une évaluation dans une année scolaire fermée.',
                ])
                ->withInput();
        }

        if ($trimestre->estFerme()) {
            return back()
                ->withErrors([
                    'trimestre_id' => 'Impossible de créer une évaluation dans un trimestre fermé.',
                ])
                ->withInput();
        }

        if ((int) $classe->annee_scolaire_id !== (int) $trimestre->annee_scolaire_id) {
            return back()
                ->withErrors([
                    'trimestre_id' => 'Le trimestre choisi n’appartient pas à la même année scolaire que la classe.',
                ])
                ->withInput();
        }

        if ($user->estEnseignant()) {
            $autorise = ClasseMatiereUser::where('user_id', $user->id)
                ->where('classe_id', $validated['classe_id'])
                ->where('matiere_id', $validated['matiere_id'])
                ->where('statut', 'actif')
                ->exists();

            if (! $autorise) {
                return back()
                    ->withErrors([
                        'classe_id' => 'Vous n’êtes pas affecté à cette classe et cette matière.',
                    ])
                    ->withInput();
            }
        }

        $existe = Evaluation::where('classe_id', $validated['classe_id'])
            ->whereDate('date_evaluation', $validated['date_evaluation'])
            ->whereTime('heure_debut', $validated['heure_debut'])
            ->whereTime('heure_fin', $validated['heure_fin'])
            ->exists();

        if ($existe) {
            return back()
                ->withErrors([
                    'date_evaluation' => 'Une évaluation existe déjà pour cette classe à cette date et ce créneau.',
                ])
                ->withInput();
        }

        $validated['user_id'] = $user->id;

        $coefficient = $this->coefficientClasseMatiere(
            (int) $validated['classe_id'],
            (int) $validated['matiere_id']
        );
        
        if ($coefficient === null) {
            return back()
                ->withErrors([
                    'matiere_id' => 'Cette matière n’est pas encore affectée à cette classe. Impossible de déterminer le coefficient.',
                ])
                ->withInput();
        }
        
        $validated['coefficient'] = $coefficient;

        Evaluation::create($validated);

        return redirect()
            ->route('evaluations.index')
            ->with('success', 'Évaluation créée avec succès.');
    }

    /**
     * Affiche le détail d’une évaluation.
     */
    public function show(Evaluation $evaluation)
    {
        $this->verifierAccesEvaluation($evaluation);

        $evaluation->load([
            'classe.anneeScolaire',
            'matiere',
            'trimestre',
            'notes.inscription.eleve',
        ]);

        $nombreElevesConcernes = Inscription::where('classe_id', $evaluation->classe_id)
            ->where('annee_scolaire_id', $evaluation->trimestre?->annee_scolaire_id)
            ->where('statut', 'actif')
            ->count();

        $nombreNotesSaisies = $evaluation->notes->count();

        $seuilMoyenne = $evaluation->bareme / 2;

        $nombreAvecMoyenne = $evaluation->notes
            ->filter(fn ($note) => $note->valeur >= $seuilMoyenne)
            ->count();

        $nombreSansMoyenne = $evaluation->notes
            ->filter(fn ($note) => $note->valeur < $seuilMoyenne)
            ->count();

        $noteMax = $evaluation->notes->max('valeur');

        $noteMin = $evaluation->notes->min('valeur');

        $moyenneEvaluation = $evaluation->notes->count() > 0
            ? round($evaluation->notes->avg('valeur'), 2)
            : null;

        $pourcentageMoyen = ($moyenneEvaluation !== null && $evaluation->bareme > 0)
            ? round(($moyenneEvaluation / $evaluation->bareme) * 100, 2)
            : null;

        return view('evaluations.show', compact(
            'evaluation',
            'nombreElevesConcernes',
            'nombreNotesSaisies',
            'noteMax',
            'noteMin',
            'moyenneEvaluation',
            'pourcentageMoyen',
            'nombreAvecMoyenne',
            'nombreSansMoyenne'
        ));
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(Evaluation $evaluation)
    {
        $this->verifierAccesEvaluation($evaluation);
        $this->verifierModificationEvaluation($evaluation);

        $user = Auth::user();

        if ($user->estGestionnaire()) {
            $classes = Classe::with('anneeScolaire')
                ->orderBy('annee_scolaire_id')
                ->orderBy('niveau')
                ->orderBy('nom')
                ->get();

            $matieres = Matiere::orderBy('nom')->get();

            $types = ['composition', 'test'];
        } else {
            $affectations = ClasseMatiereUser::with([
                    'classe.anneeScolaire',
                    'matiere',
                ])
                ->where('user_id', $user->id)
                ->where('statut', 'actif')
                ->get();

            $classes = $affectations
                ->pluck('classe')
                ->unique('id')
                ->values();

            $matieres = $affectations
                ->pluck('matiere')
                ->unique('id')
                ->values();

            $types = ['devoir', 'interrogation'];
        }

        $trimestres = Trimestre::with('anneeScolaire')
            ->orderByDesc('date_debut')
            ->get();

        return view('evaluations.edit', compact(
            'evaluation',
            'classes',
            'matieres',
            'trimestres',
            'types'
        ));
    }

    /**
     * Met à jour une évaluation.
     */
    public function update(Request $request, Evaluation $evaluation)
    {
        $this->verifierAccesEvaluation($evaluation);
        $this->verifierModificationEvaluation($evaluation);

        if ($this->evaluationEstVerrouillee($evaluation)) {
            return back()->withErrors([
                'evaluation' => 'Impossible de modifier cette évaluation : son trimestre ou son année scolaire est fermé.',
            ]);
        }

        $user = Auth::user();

        $typesAutorises = $user->estGestionnaire()
            ? ['composition', 'test']
            : ['devoir', 'interrogation'];

        $validated = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
            'trimestre_id' => ['required', 'exists:trimestres,id'],
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($typesAutorises)],
            'date_evaluation' => ['required', 'date'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:20'],
            'bareme' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        $classe = Classe::findOrFail($validated['classe_id']);
        $trimestre = Trimestre::with('anneeScolaire')->findOrFail($validated['trimestre_id']);

        if ($classe->anneeScolaire?->estFermee() || $trimestre->anneeScolaire?->estFermee()) {
            return back()
                ->withErrors([
                    'classe_id' => 'Impossible de déplacer cette évaluation vers une année scolaire fermée.',
                ])
                ->withInput();
        }

        if ($trimestre->estFerme()) {
            return back()
                ->withErrors([
                    'trimestre_id' => 'Impossible de déplacer cette évaluation vers un trimestre fermé.',
                ])
                ->withInput();
        }

        if ((int) $classe->annee_scolaire_id !== (int) $trimestre->annee_scolaire_id) {
            return back()
                ->withErrors([
                    'trimestre_id' => 'Le trimestre choisi n’appartient pas à la même année scolaire que la classe.',
                ])
                ->withInput();
        }

        if ($user->estEnseignant()) {
            $autorise = ClasseMatiereUser::where('user_id', $user->id)
                ->where('classe_id', $validated['classe_id'])
                ->where('matiere_id', $validated['matiere_id'])
                ->where('statut', 'actif')
                ->exists();

            if (! $autorise) {
                return back()
                    ->withErrors([
                        'classe_id' => 'Vous n’êtes pas affecté à cette classe et cette matière.',
                    ])
                    ->withInput();
            }
        }

        $existe = Evaluation::where('classe_id', $validated['classe_id'])
            ->whereDate('date_evaluation', $validated['date_evaluation'])
            ->whereTime('heure_debut', $validated['heure_debut'])
            ->whereTime('heure_fin', $validated['heure_fin'])
            ->where('id', '!=', $evaluation->id)
            ->exists();

        if ($existe) {
            return back()
                ->withErrors([
                    'date_evaluation' => 'Une autre évaluation existe déjà pour cette classe à cette date et ce créneau.',
                ])
                ->withInput();
        }

        $classeChangee = (int) $evaluation->classe_id !== (int) $validated['classe_id'];

        $matiereChangee = (int) $evaluation->matiere_id !== (int) $validated['matiere_id'];

        if ($classeChangee || $matiereChangee) {
            $coefficient = $this->coefficientClasseMatiere(
                (int) $validated['classe_id'],
                (int) $validated['matiere_id']
            );

            if ($coefficient === null) {
                return back()
                    ->withErrors([
                        'matiere_id' => 'Cette matière n’est pas encore affectée à cette classe. Impossible de déterminer le coefficient.',
                    ])
                    ->withInput();
            }

            $validated['coefficient'] = $coefficient;
        } else {
            unset($validated['coefficient']);
        }

        $evaluation->update($validated);

        return redirect()
            ->route('evaluations.index')
            ->with('success', 'Évaluation modifiée avec succès.');
    }

    /**
     * Supprime logiquement une évaluation.
     */
    public function destroy(Evaluation $evaluation)
    {
        $this->verifierAccesEvaluation($evaluation);
        $this->verifierModificationEvaluation($evaluation);

        if ($this->evaluationEstVerrouillee($evaluation)) {
            return back()->withErrors([
                'evaluation' => 'Impossible de supprimer cette évaluation : son trimestre ou son année scolaire est fermé.',
            ]);
        }

        $evaluation->update([
            'is_deleted' => true,
        ]);

        $evaluation->delete();

        return redirect()
            ->route('evaluations.index')
            ->with('success', 'Évaluation supprimée avec succès.');
    }

    /**
     * Vérifie qu’un enseignant peut accéder à cette évaluation.
     */
    private function verifierAccesEvaluation(Evaluation $evaluation): void
    {
        $user = Auth::user();

        if ($user->estGestionnaire()) {
            return;
        }

        $autorise = ClasseMatiereUser::where('user_id', $user->id)
            ->where('classe_id', $evaluation->classe_id)
            ->where('matiere_id', $evaluation->matiere_id)
            ->where('statut', 'actif')
            ->exists();

        if (! $autorise) {
            abort(403, 'Accès refusé.');
        }
    }

    /**
     * Vérifie qu’un utilisateur peut modifier ou supprimer une évaluation.
     *
     * Gestionnaire :
     * - peut modifier toutes les évaluations
     *
     * Enseignant :
     * - peut modifier uniquement les évaluations qu’il a créées lui-même
     * - ne peut pas modifier les compositions / tests créés par le gestionnaire
     * - ne peut pas modifier les évaluations créées par un autre enseignant
     */
    private function verifierModificationEvaluation(Evaluation $evaluation): void
    {
        $user = Auth::user();

        if ($user->estGestionnaire()) {
            return;
        }

        if (! $user->estEnseignant()) {
            abort(403, 'Accès refusé.');
        }

        if ((int) $evaluation->user_id !== (int) $user->id) {
            abort(403, 'Vous ne pouvez modifier que les évaluations que vous avez créées.');
        }

        if (! in_array($evaluation->type, ['devoir', 'interrogation'], true)) {
            abort(403, 'Seul le gestionnaire peut modifier ce type d’évaluation.');
        }

        $this->verifierAccesEvaluation($evaluation);
    }

    private function evaluationEstVerrouillee(Evaluation $evaluation): bool
    {
        $evaluation->loadMissing([
            'classe.anneeScolaire',
            'trimestre.anneeScolaire',
        ]);

        return $evaluation->trimestre?->estFerme()
            || $evaluation->classe?->anneeScolaire?->estFermee()
            || $evaluation->trimestre?->anneeScolaire?->estFermee();
    }

    /**
     * Récupère le coefficient d'une matière dans une classe.
     */
    private function coefficientClasseMatiere(int $classeId, int $matiereId): ?float
    {
        $affectation = ClasseMatiereUser::where('classe_id', $classeId)
            ->where('matiere_id', $matiereId)
            ->where('statut', 'actif')
            ->orderByDesc('date_debut')
            ->first();

        if (! $affectation) {
            return null;
        }

        return (float) $affectation->coefficient;
    }

}
