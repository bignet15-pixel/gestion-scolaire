<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\ClasseMatiereUser;
use Illuminate\Http\Request;

class AnneeScolaireController extends Controller
{
    /**
     * Affiche la liste des années scolaires.
     */
    public function index()
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('annee_scolaires.index', compact('annees'));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        return view('annee_scolaires.create');
    }

    /**
     * Enregistre une nouvelle année scolaire.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle' => ['required', 'string', 'max:255', 'unique:annee_scolaires,libelle'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'statut' => ['required', 'in:active,fermee'],
        ]);

        if ($validated['statut'] === 'active') {
            $anneesFermees = AnneeScolaire::where('statut', 'active')->get();

            foreach ($anneesFermees as $anneeFermee) {
                $anneeFermee->update([
                    'statut' => 'fermee',
                ]);

                $this->terminerAffectationsDeAnnee($anneeFermee);
            }
        }

        AnneeScolaire::create($validated);

        return redirect()
            ->route('annee-scolaires.index')
            ->with('success', 'Année scolaire créée avec succès.');
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(AnneeScolaire $annee_scolaire)
    {
        return view('annee_scolaires.edit', compact('annee_scolaire'));
    }

    /**
     * Met à jour une année scolaire.
     */
    public function update(Request $request, AnneeScolaire $annee_scolaire)
    {
        $validated = $request->validate([
            'libelle' => ['required', 'string', 'max:255', 'unique:annee_scolaires,libelle,' . $annee_scolaire->id],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'statut' => ['required', 'in:active,fermee'],
        ]);

        if ($validated['statut'] === 'active') {
            $anneesFermees = AnneeScolaire::where('id', '!=', $annee_scolaire->id)
                ->where('statut', 'active')
                ->get();

            foreach ($anneesFermees as $anneeFermee) {
                $anneeFermee->update([
                    'statut' => 'fermee',
                ]);

                $this->terminerAffectationsDeAnnee($anneeFermee);
            }
        }

        $annee_scolaire->update($validated);

        if ($validated['statut'] === 'fermee') {
            $this->terminerAffectationsDeAnnee($annee_scolaire);
        }

        return redirect()
            ->route('annee-scolaires.index')
            ->with('success', 'Année scolaire modifiée avec succès.');
    }

    /**
     * Supprime logiquement une année scolaire.
     */
    public function destroy(AnneeScolaire $annee_scolaire)
    {
        $annee_scolaire->update([
            'is_deleted' => true,
        ]);

        $annee_scolaire->delete();

        return redirect()
            ->route('annee-scolaires.index')
            ->with('success', 'Année scolaire supprimée avec succès.');
    }

    /**
     * Active une année scolaire.
     */
    public function activer(AnneeScolaire $annee_scolaire)
    {
        $anneesFermees = AnneeScolaire::where('statut', 'active')
            ->where('id', '!=', $annee_scolaire->id)
            ->get();

        foreach ($anneesFermees as $anneeFermee) {
            $anneeFermee->update([
                'statut' => 'fermee',
            ]);

            $this->terminerAffectationsDeAnnee($anneeFermee);
        }

        $annee_scolaire->update([
            'statut' => 'active',
        ]);

        return redirect()
            ->route('annee-scolaires.index')
            ->with('success', 'Année scolaire activée avec succès.');
    }

    /**
     * Ferme une année scolaire.
     */
    public function fermer(AnneeScolaire $annee_scolaire)
    {
        $annee_scolaire->update([
            'statut' => 'fermee',
        ]);

        $this->terminerAffectationsDeAnnee($annee_scolaire);

        return redirect()
            ->route('annee-scolaires.index')
            ->with('success', 'Année scolaire fermée avec succès.');
    }

    /**
     * Termine les affectations actives des classes d'une année fermée.
     */
    private function terminerAffectationsDeAnnee(AnneeScolaire $annee): void
    {
        ClasseMatiereUser::whereHas('classe', function ($query) use ($annee) {
                $query->where('annee_scolaire_id', $annee->id);
            })
            ->where('statut', 'actif')
            ->update([
                'statut' => 'termine',
                'date_fin' => now()->toDateString(),
            ]);
    }
}
