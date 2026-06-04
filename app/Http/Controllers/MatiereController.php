<?php

namespace App\Http\Controllers;

use App\Models\Matiere;
use Illuminate\Http\Request;
use App\Models\AnneeScolaire;
use App\Models\Classe;

class MatiereController extends Controller
{
    /**
     * Affiche la liste des matières avec filtres année / classe.
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

        $matieresQuery = Matiere::withCount('affectations')
            ->orderBy('nom');

        if ($selectedAnneeId || $selectedClasseId) {
            $matieresQuery->whereHas('affectations.classe', function ($query) use ($selectedAnneeId, $selectedClasseId) {
                if ($selectedAnneeId) {
                    $query->where('annee_scolaire_id', $selectedAnneeId);
                }

                if ($selectedClasseId) {
                    $query->where('classes.id', $selectedClasseId);
                }
            });
        }

        $matieres = $matieresQuery->get();

        return view('matieres.index', compact(
            'matieres',
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
        return view('matieres.create');
    }

    /**
     * Enregistre une nouvelle matière.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:matieres,nom'],
            'coefficient_default' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        Matiere::create($validated);

        return redirect()
            ->route('matieres.index')
            ->with('success', 'Matière créée avec succès.');
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(Matiere $matiere)
    {
        return view('matieres.edit', compact('matiere'));
    }

    /**
     * Met à jour une matière.
     */
    public function update(Request $request, Matiere $matiere)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:matieres,nom,' . $matiere->id],
            'coefficient_default' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $matiere->update($validated);

        return redirect()
            ->route('matieres.index')
            ->with('success', 'Matière modifiée avec succès.');
    }

    /**
     * Supprime logiquement une matière si elle n'est pas encore utilisée.
     */
    public function destroy(Matiere $matiere)
    {
        if ($matiere->affectations()->exists()) {
            return redirect()
                ->route('matieres.index')
                ->withErrors([
                    'matiere' => 'Impossible de supprimer cette matière : elle est déjà utilisée dans une affectation.',
                ]);
        }

        $matiere->update([
            'is_deleted' => true,
        ]);

        $matiere->delete();

        return redirect()
            ->route('matieres.index')
            ->with('success', 'Matière supprimée avec succès.');
    }
}