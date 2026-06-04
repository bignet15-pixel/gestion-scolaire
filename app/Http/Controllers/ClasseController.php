<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ClasseMatiereUser;
use App\Models\Evaluation;

class ClasseController extends Controller
{
    /**
     * Affiche la liste des classes avec filtres.
     */
    public function index(Request $request)
    {
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $selectedNiveau = $request->input('niveau');

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $niveaux = ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];

        $classes = Classe::with([
                'anneeScolaire',
                'enseignantPrincipal',
            ])
            ->withCount('inscriptions')
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->when($selectedNiveau, function ($query) use ($selectedNiveau) {
                $query->where('niveau', $selectedNiveau);
            })
            ->orderBy('annee_scolaire_id')
            ->orderBy('niveau')
            ->orderBy('nom')
            ->get();

        return view('classes.index', compact(
            'classes',
            'annees',
            'niveaux',
            'selectedAnneeId',
            'selectedNiveau'
        ));
    }

    /**
     * Affiche le formulaire de création d'une classe.
     */
    public function create()
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $enseignants = User::where('role', 'enseignant')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('classes.create', compact('annees', 'enseignants'));
    }

    /**
     * Enregistre une nouvelle classe.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],

            'enseignant_principal_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'enseignant'),
            ],

            'niveau' => ['required', 'in:CP1,CP2,CE1,CE2,CM1,CM2'],

            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('classes', 'nom')->where(function ($query) use ($request) {
                    return $query->where('annee_scolaire_id', $request->annee_scolaire_id);
                }),
            ],

            'frais_scolarite' => ['required', 'numeric', 'min:0'],
        ]);

        $annee = AnneeScolaire::findOrFail($validated['annee_scolaire_id']);

        if ($annee->estFermee()) {
            return back()
                ->withErrors([
                    'annee_scolaire_id' => 'Impossible de créer une classe dans une année scolaire fermée.',
                ])
                ->withInput();
        }

        Classe::create($validated);

        return redirect()
            ->route('classes.index')
            ->with('success', 'Classe créée avec succès.');
    }

    /**
     * Affiche le détail d'une classe.
     */
    public function show(Classe $classe)
    {
        $classe->load([
            'anneeScolaire',
            'enseignantPrincipal',
            'chefClasse',
            'affectations.enseignant',
            'affectations.matiere',
        ]);

        $inscriptions = $classe->inscriptions()
            ->with('eleve')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->select('inscriptions.*')
            ->get();

        return view('classes.show', compact('classe', 'inscriptions'));
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(Classe $classe)
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $enseignants = User::where('role', 'enseignant')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $eleves = $classe->eleves()
            ->wherePivot('statut', 'actif')
            ->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->get();

        return view('classes.edit', compact('classe', 'annees', 'enseignants', 'eleves'));
    }

    /**
     * Met à jour une classe.
     */
    public function update(Request $request, Classe $classe)
    {
        if ($classe->anneeScolaire?->estFermee()) {
            return back()->withErrors([
                'classe' => 'Impossible de modifier cette classe : son année scolaire est fermée.',
            ]);
        }

        $validated = $request->validate([
            'annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],

            'enseignant_principal_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'enseignant'),
            ],

            'niveau' => ['required', 'in:CP1,CP2,CE1,CE2,CM1,CM2'],

            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('classes', 'nom')
                    ->where(function ($query) use ($request) {
                        return $query->where('annee_scolaire_id', $request->annee_scolaire_id);
                    })
                    ->ignore($classe->id),
            ],

            'frais_scolarite' => ['required', 'numeric', 'min:0'],

            'chef_classe_id' => ['nullable', 'exists:eleves,id'],
        ]);

        $annee = AnneeScolaire::findOrFail($validated['annee_scolaire_id']);

        if ($annee->estFermee()) {
            return back()
                ->withErrors([
                    'annee_scolaire_id' => 'Impossible de déplacer cette classe vers une année scolaire fermée.',
                ])
                ->withInput();
        }

        if (! empty($validated['chef_classe_id'])) {
            $estInscrit = $classe->inscriptions()
                ->where('eleve_id', $validated['chef_classe_id'])
                ->where('statut', 'actif')
                ->exists();

            if (! $estInscrit) {
                return back()
                    ->withErrors([
                        'chef_classe_id' => 'Le chef de classe doit être un élève actif inscrit dans cette classe.',
                    ])
                    ->withInput();
            }
        }

        $classe->update($validated);

        return redirect()
            ->route('classes.index')
            ->with('success', 'Classe modifiée avec succès.');
    }

    /**
     * Supprime logiquement une classe si elle n'a pas encore de données liées.
     */
    public function destroy(Classe $classe)
    {
        if ($classe->anneeScolaire?->estFermee()) {
            return back()->withErrors([
                'classe' => 'Impossible de supprimer cette classe : son année scolaire est fermée.',
            ]);
        }

        $aDesInscriptions = $classe->inscriptions()->exists();

        $aDesAffectations = ClasseMatiereUser::where('classe_id', $classe->id)->exists();

        $aDesEvaluations = Evaluation::where('classe_id', $classe->id)->exists();

        if ($aDesInscriptions || $aDesAffectations || $aDesEvaluations) {
            return redirect()
                ->route('classes.index')
                ->withErrors([
                    'classe' => 'Impossible de supprimer cette classe : elle contient déjà des inscriptions, des affectations ou des évaluations. Modifiez-la plutôt au lieu de la supprimer.',
                ]);
        }

        $classe->update([
            'is_deleted' => true,
        ]);

        $classe->delete();

        return redirect()
            ->route('classes.index')
            ->with('success', 'Classe supprimée avec succès.');
    }
}
