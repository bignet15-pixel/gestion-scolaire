<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Trimestre;
use Illuminate\Http\Request;

class TrimestreController extends Controller
{
    /**
     * Affiche la liste des trimestres.
     */
    public function index()
    {
        $trimestres = Trimestre::with('anneeScolaire')
            ->orderByDesc('created_at')
            ->get();

        return view('trimestres.index', compact('trimestres'));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('trimestres.create', compact('annees'));
    }

    /**
     * Enregistre un nouveau trimestre.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],
            'nom' => ['required', 'string', 'max:255'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['required', 'in:actif,ferme'],
        ]);

        $annee = AnneeScolaire::findOrFail($validated['annee_scolaire_id']);

        if ($annee->estFermee()) {
            return back()
                ->withErrors([
                    'annee_scolaire_id' => 'Impossible de créer un trimestre dans une année scolaire fermée.',
                ])
                ->withInput();
        }

        if ($validated['statut'] === 'ferme') {
            $validated['date_fin'] = now()->toDateString();
        }

        Trimestre::create($validated);

        return redirect()
            ->route('trimestres.index')
            ->with('success', 'Trimestre créé avec succès.');
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(Trimestre $trimestre)
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('trimestres.edit', compact('trimestre', 'annees'));
    }

    /**
     * Met à jour un trimestre.
     */
    public function update(Request $request, Trimestre $trimestre)
    {
        if ($this->trimestreEstVerrouille($trimestre)) {
            return back()->withErrors([
                'trimestre' => 'Impossible de modifier ce trimestre : il est fermé ou son année scolaire est fermée.',
            ]);
        }

        $validated = $request->validate([
            'annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],
            'nom' => ['required', 'string', 'max:255'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['required', 'in:actif,ferme'],
        ]);

        $annee = AnneeScolaire::findOrFail($validated['annee_scolaire_id']);

        if ($annee->estFermee()) {
            return back()
                ->withErrors([
                    'annee_scolaire_id' => 'Impossible de déplacer ce trimestre vers une année scolaire fermée.',
                ])
                ->withInput();
        }

        if ($validated['statut'] === 'ferme') {
            if ($message = $trimestre->messageBlocageFermeture()) {
                return back()
                    ->withErrors(['trimestre' => $message])
                    ->withInput();
            }

            $validated['date_fin'] = now()->toDateString();
        }

        $trimestre->update($validated);

        return redirect()
            ->route('trimestres.index')
            ->with('success', 'Trimestre modifié avec succès.');
    }

    /**
     * Supprime logiquement un trimestre.
     */
    public function destroy(Trimestre $trimestre)
    {
        if ($this->trimestreEstVerrouille($trimestre)) {
            return back()->withErrors([
                'trimestre' => 'Impossible de supprimer ce trimestre : il est fermé ou son année scolaire est fermée.',
            ]);
        }

        $trimestre->update([
            'is_deleted' => true,
        ]);

        $trimestre->delete();

        return redirect()
            ->route('trimestres.index')
            ->with('success', 'Trimestre supprimé avec succès.');
    }

    /**
     * Ferme un trimestre.
     */
    public function fermer(Trimestre $trimestre)
    {
        if ($trimestre->anneeScolaire?->estFermee()) {
            return back()->withErrors([
                'trimestre' => 'Impossible de modifier ce trimestre : son année scolaire est fermée.',
            ]);
        }

        if ($message = $trimestre->messageBlocageFermeture()) {
            return back()->withErrors([
                'trimestre' => $message,
            ]);
        }

        $trimestre->update([
            'statut' => 'ferme',
            'date_fin' => now()->toDateString(),
        ]);

        return redirect()
            ->route('trimestres.index')
            ->with('success', 'Trimestre fermé avec succès.');
    }

    /**
     * Active un trimestre.
     */
    public function activer(Trimestre $trimestre)
    {
        $trimestre->loadMissing('anneeScolaire');

        if ($trimestre->anneeScolaire?->estFermee()) {
            return back()->withErrors([
                'trimestre' => 'Impossible d’activer ce trimestre : son année scolaire est fermée.',
            ]);
        }

        $trimestre->update([
            'statut' => 'actif',
        ]);

        return redirect()
            ->route('trimestres.index')
            ->with('success', 'Trimestre activé avec succès.');
    }

    private function trimestreEstVerrouille(Trimestre $trimestre): bool
    {
        $trimestre->loadMissing('anneeScolaire');

        return $trimestre->estFerme() || $trimestre->anneeScolaire?->estFermee();
    }

}
