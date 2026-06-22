<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EleveParentController extends Controller
{
    /**
     * Lie un compte parent à un élève.
     */
    public function store(Request $request, Eleve $eleve)
    {
        $validated = $request->validate([
            'parent_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'parent'),
            ],
            'lien_parente' => ['nullable', 'string', 'max:100'],
            'responsable_principal' => ['nullable', 'boolean'],
        ]);

        $responsablePrincipal = $request->boolean('responsable_principal');

        if ($responsablePrincipal) {
            DB::table('eleve_parent')
                ->where('eleve_id', $eleve->id)
                ->update(['responsable_principal' => false]);
        }

        $eleve->parents()->syncWithoutDetaching([
            $validated['parent_id'] => [
                'lien_parente' => $validated['lien_parente'] ?? null,
                'responsable_principal' => $responsablePrincipal,
            ],
        ]);

        $eleve->parents()->updateExistingPivot($validated['parent_id'], [
            'lien_parente' => $validated['lien_parente'] ?? null,
            'responsable_principal' => $responsablePrincipal,
        ]);

        return redirect()
            ->route('eleves.show', $eleve)
            ->with('success', 'Parent lié à l’élève avec succès.');
    }

    /**
     * Retire la liaison entre un compte parent et un élève.
     */
    public function destroy(Eleve $eleve, int $parent)
    {
        $eleve->parents()->detach($parent);

        return redirect()
            ->route('eleves.show', $eleve)
            ->with('success', 'Parent retiré de l’élève avec succès.');
    }
}
