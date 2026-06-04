<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EnseignantController extends Controller
{
    /**
     * Affiche la liste des enseignants.
     */
    public function index()
    {
        $enseignants = User::where('role', 'enseignant')
            ->withCount([
                'affectations as affectations_actives_count' => function ($query) {
                    $query->where('statut', 'actif');
                },
            ])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('enseignants.index', compact('enseignants'));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        return view('enseignants.create');
    }

    /**
     * Enregistre un nouvel enseignant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['required', 'in:M,F'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $validated['name'] = $validated['nom'] . ' ' . $validated['prenom'];
        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'enseignant';
        $validated['matricule'] = $this->genererMatriculeEnseignant();

        User::create($validated);

        return redirect()
            ->route('enseignants.index')
            ->with('success', 'Enseignant créé avec succès.');
    }

    /**
     * Affiche le détail d'un enseignant.
     */
    public function show(User $enseignant)
    {
        $this->verifierEnseignant($enseignant);

        $enseignant->load([
            'affectations.classe.anneeScolaire',
            'affectations.matiere',
        ]);

        $nombreAffectationsActives = $enseignant->affectations
            ->where('statut', 'actif')
            ->count();

        $nombreClasses = $enseignant->affectations
            ->where('statut', 'actif')
            ->pluck('classe_id')
            ->unique()
            ->count();

        $nombreMatieres = $enseignant->affectations
            ->where('statut', 'actif')
            ->pluck('matiere_id')
            ->unique()
            ->count();

        return view('enseignants.show', compact(
            'enseignant',
            'nombreAffectationsActives',
            'nombreClasses',
            'nombreMatieres'
        ));
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(User $enseignant)
    {
        $this->verifierEnseignant($enseignant);

        return view('enseignants.edit', compact('enseignant'));
    }

    /**
     * Met à jour un enseignant.
     */
    public function update(Request $request, User $enseignant)
    {
        $this->verifierEnseignant($enseignant);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['required', 'in:M,F'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($enseignant->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore($enseignant->id),
            ],
            'adresse' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $validated['name'] = $validated['nom'] . ' ' . $validated['prenom'];

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $enseignant->update($validated);

        return redirect()
            ->route('enseignants.index')
            ->with('success', 'Enseignant modifié avec succès.');
    }

    /**
     * Désactive un enseignant si aucune affectation active ne dépend de lui.
     */
    public function destroy(User $enseignant)
    {
        $this->verifierEnseignant($enseignant);

        $aDesAffectationsActives = $enseignant->affectations()
            ->where('statut', 'actif')
            ->exists();

        if ($aDesAffectationsActives) {
            return redirect()
                ->route('enseignants.index')
                ->withErrors([
                    'enseignant' => 'Impossible de désactiver cet enseignant : il possède encore des affectations actives. Terminez ou suspendez d’abord ses affectations.',
                ]);
        }

        $enseignant->update([
            'is_deleted' => true,
        ]);

        $enseignant->delete();

        return redirect()
            ->route('enseignants.index')
            ->with('success', 'Enseignant désactivé avec succès.');
    }

    /**
     * Génère un matricule enseignant.
     */
    private function genererMatriculeEnseignant(): string
    {
        $dernier = User::where('role', 'enseignant')
            ->whereNotNull('matricule')
            ->where('matricule', 'like', 'ENS-%')
            ->orderByDesc('id')
            ->first();

        if (! $dernier) {
            return 'ENS-0001';
        }

        $numero = (int) str_replace('ENS-', '', $dernier->matricule);
        $numero++;

        return 'ENS-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Vérifie que l'utilisateur manipulé est bien un enseignant.
     */
    private function verifierEnseignant(User $enseignant): void
    {
        if ($enseignant->role !== 'enseignant') {
            abort(404);
        }
    }
}