<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\Matiere;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\AnneeScolaire;


class ClasseMatiereUserController extends Controller
{
    /**
     * Affiche la liste des affectations avec filtres année / classe.
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

        $affectations = ClasseMatiereUser::with([
                'classe.anneeScolaire',
                'matiere',
                'enseignant',
                'emploisDuTemps',
            ])
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->whereHas('classe', function ($q) use ($selectedAnneeId) {
                    $q->where('annee_scolaire_id', $selectedAnneeId);
                });
            })
            ->when($selectedClasseId, function ($query) use ($selectedClasseId) {
                $query->where('classe_id', $selectedClasseId);
            })
            ->orderByDesc('created_at')
            ->get();

        return view('affectations.index', compact(
            'affectations',
            'annees',
            'classes',
            'selectedAnneeId',
            'selectedClasseId'
        ));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        $classes = Classe::with('anneeScolaire')
            ->orderBy('annee_scolaire_id')
            ->orderBy('niveau')
            ->orderBy('nom')
            ->get();

        $matieres = Matiere::orderBy('nom')->get();

        $enseignants = User::where('role', 'enseignant')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('affectations.create', compact('classes', 'matieres', 'enseignants'));
    }

    /**
     * Enregistre une affectation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'enseignant'),
            ],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:20'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['required', 'in:actif,termine,suspendu'],
        ]);

        $classe = Classe::with('anneeScolaire')->findOrFail($validated['classe_id']);

        if ($classe->anneeScolaire?->estFermee()) {
            return back()
                ->withErrors([
                    'classe_id' => 'Impossible de créer une affectation dans une année scolaire fermée.',
                ])
                ->withInput();
        }

        $dejaActive = ClasseMatiereUser::where('classe_id', $validated['classe_id'])
            ->where('matiere_id', $validated['matiere_id'])
            ->where('statut', 'actif')
            ->exists();

        if ($dejaActive && $validated['statut'] === 'actif') {
            return back()
                ->withErrors([
                    'matiere_id' => 'Cette matière a déjà un enseignant actif dans cette classe.',
                ])
                ->withInput();
        }

        ClasseMatiereUser::create($validated);

        return redirect()
            ->route('affectations.index')
            ->with('success', 'Affectation créée avec succès.');
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(ClasseMatiereUser $affectation)
    {
        $classes = Classe::with('anneeScolaire')
            ->orderBy('annee_scolaire_id')
            ->orderBy('niveau')
            ->orderBy('nom')
            ->get();

        $matieres = Matiere::orderBy('nom')->get();

        $enseignants = User::where('role', 'enseignant')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('affectations.edit', compact(
            'affectation',
            'classes',
            'matieres',
            'enseignants'
        ));
    }

    /**
     * Met à jour une affectation.
     */
    public function update(Request $request, ClasseMatiereUser $affectation)
    {
        if ($this->affectationEstVerrouillee($affectation)) {
            return back()->withErrors([
                'affectation' => 'Impossible de modifier cette affectation : son année scolaire est fermée.',
            ]);
        }

        $validated = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'enseignant'),
            ],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:20'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['required', 'in:actif,termine,suspendu'],
        ]);

        $classe = Classe::with('anneeScolaire')->findOrFail($validated['classe_id']);

        if ($classe->anneeScolaire?->estFermee()) {
            return back()
                ->withErrors([
                    'classe_id' => 'Impossible de déplacer cette affectation vers une année scolaire fermée.',
                ])
                ->withInput();
        }

        $dejaActive = ClasseMatiereUser::where('classe_id', $validated['classe_id'])
            ->where('matiere_id', $validated['matiere_id'])
            ->where('statut', 'actif')
            ->where('id', '!=', $affectation->id)
            ->exists();

        if ($dejaActive && $validated['statut'] === 'actif') {
            return back()
                ->withErrors([
                    'matiere_id' => 'Cette matière a déjà un enseignant actif dans cette classe.',
                ])
                ->withInput();
        }

        $affectation->update($validated);

        return redirect()
            ->route('affectations.index')
            ->with('success', 'Affectation modifiée avec succès.');
    }

    /**
     * Supprime logiquement une affectation si elle n'est pas utilisée.
     */
    public function destroy(ClasseMatiereUser $affectation)
    {
        if ($this->affectationEstVerrouillee($affectation)) {
            return back()->withErrors([
                'affectation' => 'Impossible de supprimer cette affectation : son année scolaire est fermée.',
            ]);
        }

        if ($affectation->emploisDuTemps()->exists()) {
            return redirect()
                ->route('affectations.index')
                ->withErrors([
                    'affectation' => 'Impossible de supprimer cette affectation : elle est déjà utilisée dans l’emploi du temps. Utilisez plutôt Terminer ou Suspendre.',
                ]);
        }

        $affectation->update([
            'is_deleted' => true,
        ]);

        $affectation->delete();

        return redirect()
            ->route('affectations.index')
            ->with('success', 'Affectation supprimée avec succès.');
    }

    /**
     * Termine une affectation.
     */
    public function terminer(ClasseMatiereUser $affectation)
    {
        if ($this->affectationEstVerrouillee($affectation)) {
            return back()->withErrors([
                'affectation' => 'Impossible de terminer cette affectation : son année scolaire est fermée.',
            ]);
        }

        $affectation->update([
            'statut' => 'termine',
            'date_fin' => now()->toDateString(),
        ]);

        return redirect()
            ->route('affectations.index')
            ->with('success', 'Affectation terminée avec succès.');
    }

    /**
     * Suspend une affectation.
     */
    public function suspendre(ClasseMatiereUser $affectation)
    {
        if ($this->affectationEstVerrouillee($affectation)) {
            return back()->withErrors([
                'affectation' => 'Impossible de suspendre cette affectation : son année scolaire est fermée.',
            ]);
        }

        $affectation->update([
            'statut' => 'suspendu',
        ]);

        return redirect()
            ->route('affectations.index')
            ->with('success', 'Affectation suspendue avec succès.');
    }

    /**
     * Réactive une affectation.
     */
    public function reactiver(ClasseMatiereUser $affectation)
    {
        if ($this->affectationEstVerrouillee($affectation)) {
            return back()->withErrors([
                'affectation' => 'Impossible de réactiver cette affectation : son année scolaire est fermée.',
            ]);
        }

        $dejaActive = ClasseMatiereUser::where('classe_id', $affectation->classe_id)
            ->where('matiere_id', $affectation->matiere_id)
            ->where('statut', 'actif')
            ->where('id', '!=', $affectation->id)
            ->exists();

        if ($dejaActive) {
            return back()->withErrors([
                'affectation' => 'Impossible de réactiver : cette matière a déjà un enseignant actif dans cette classe.',
            ]);
        }

        $affectation->update([
            'statut' => 'actif',
            'date_fin' => null,
        ]);

        return redirect()
            ->route('affectations.index')
            ->with('success', 'Affectation réactivée avec succès.');
    }

    private function affectationEstVerrouillee(ClasseMatiereUser $affectation): bool
    {
        $affectation->loadMissing('classe.anneeScolaire');

        return $affectation->classe?->anneeScolaire?->estFermee() ?? false;
    }
}
