<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ParentController extends Controller
{
    /**
     * Affiche la liste des comptes parents.
     */
    public function index()
    {
        $parents = User::where('role', 'parent')
            ->withCount('enfants')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('parents.index', compact('parents'));
    }

    /**
     * Affiche le formulaire de création d'un compte parent.
     */
    public function create()
    {
        return view('parents.create');
    }

    /**
     * Enregistre un nouveau compte parent.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['nullable', 'in:M,F'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $validated['name'] = trim($validated['prenom'].' '.$validated['nom']);
        $validated['role'] = 'parent';
        $validated['matricule'] = $this->genererMatriculeParent();
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Compte parent créé avec succès.');
    }

    /**
     * Affiche le formulaire de modification d'un compte parent.
     */
    public function edit(User $parent)
    {
        abort_unless($parent->role === 'parent', 404);

        return view('parents.edit', compact('parent'));
    }

    /**
     * Met à jour un compte parent.
     */
    public function update(Request $request, User $parent)
    {
        abort_unless($parent->role === 'parent', 404);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['nullable', 'in:M,F'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($parent->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore($parent->id),
            ],
            'adresse' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $validated['name'] = trim($validated['prenom'].' '.$validated['nom']);
        $validated['role'] = 'parent';

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $parent->update($validated);

        return redirect()
            ->route('parents.index')
            ->with('success', 'Compte parent modifié avec succès.');
    }

    /**
     * Supprime logiquement un compte parent.
     */
    public function destroy(User $parent)
    {
        abort_unless($parent->role === 'parent', 404);

        $parent->enfants()->detach();

        $parent->update([
            'is_deleted' => true,
        ]);

        $parent->delete();

        return redirect()
            ->route('parents.index')
            ->with('success', 'Compte parent supprimé avec succès.');
    }

    /**
     * Génère automatiquement le matricule d'un parent.
     */
    private function genererMatriculeParent(): string
    {
        $plusGrandNumero = User::withTrashed()
            ->whereNotNull('matricule')
            ->where('matricule', 'like', 'PAR-%')
            ->pluck('matricule')
            ->map(function ($matricule) {
                if (preg_match('/^PAR-(\d+)$/', $matricule, $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter(fn ($numero) => $numero !== null)
            ->max() ?? 0;

        $numero = $plusGrandNumero + 1;
        $matricule = 'PAR-'.str_pad($numero, 4, '0', STR_PAD_LEFT);

        while (User::withTrashed()->where('matricule', $matricule)->exists()) {
            $numero++;
            $matricule = 'PAR-'.str_pad($numero, 4, '0', STR_PAD_LEFT);
        }

        return $matricule;
    }
}
