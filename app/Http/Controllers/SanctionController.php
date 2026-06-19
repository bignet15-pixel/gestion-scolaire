<?php

namespace App\Http\Controllers;

use App\Models\Sanction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SanctionController extends Controller
{
    public function index(Request $request)
    {
        $selectedCategorie = in_array($request->input('categorie'), Sanction::CATEGORIES, true)
            ? $request->input('categorie')
            : null;
        $selectedMode = in_array($request->input('mode_declenchement'), Sanction::MODES_DECLENCHEMENT, true)
            ? $request->input('mode_declenchement')
            : null;
        $selectedActive = $request->input('active');

        $sanctions = Sanction::with('createdBy')
            ->when($selectedCategorie, fn ($query) => $query->where('categorie', $selectedCategorie))
            ->when($selectedMode, fn ($query) => $query->where('mode_declenchement', $selectedMode))
            ->when($selectedActive !== null && $selectedActive !== '', function ($query) use ($selectedActive) {
                $query->where('active', (bool) $selectedActive);
            })
            ->orderByDesc('active')
            ->orderBy('niveau_gravite')
            ->orderBy('nom')
            ->get();

        return view('sanctions.index', compact(
            'sanctions',
            'selectedCategorie',
            'selectedMode',
            'selectedActive'
        ));
    }

    public function create()
    {
        return view('sanctions.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validerSanction($request);
        $validated['created_by'] = Auth::id();

        $sanction = Sanction::create($validated);

        return redirect()
            ->route('sanctions.show', $sanction)
            ->with('success', 'Sanction configurée avec succès.');
    }

    public function show(Sanction $sanction)
    {
        $sanction->load(['createdBy', 'sanctionsAppliquees']);

        return view('sanctions.show', compact('sanction'));
    }

    public function edit(Sanction $sanction)
    {
        return view('sanctions.edit', compact('sanction'));
    }

    public function update(Request $request, Sanction $sanction)
    {
        $sanction->update($this->validerSanction($request, $sanction));

        return redirect()
            ->route('sanctions.show', $sanction)
            ->with('success', 'Configuration de sanction modifiée avec succès.');
    }

    public function destroy(Sanction $sanction)
    {
        $sanction->update(['active' => false]);
        $sanction->delete();

        return redirect()
            ->route('sanctions.index')
            ->with('success', 'Sanction désactivée et supprimée de la configuration active.');
    }

    private function validerSanction(Request $request, ?Sanction $sanction = null): array
    {
        $declenchementParSeuil = $request->input('categorie') !== 'conduite' && in_array(
            $request->input('mode_declenchement'),
            ['automatique', 'mixte'],
            true
        );
        $pointsEnMoins = $request->input('type_effet') === 'points_en_moins';
        $nomUnique = Rule::unique('sanctions', 'nom')->whereNull('deleted_at');

        if ($sanction) {
            $nomUnique->ignore($sanction->id);
        }

        if ($request->input('categorie') === 'conduite'
            && $request->input('mode_declenchement') === 'automatique') {
            throw ValidationException::withMessages([
                'mode_declenchement' => 'Une sanction de conduite doit être manuelle ou mixte.',
            ]);
        }

        $validated = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:255',
                $nomUnique,
            ],
            'description' => ['nullable', 'string', 'max:3000'],
            'categorie' => ['required', Rule::in(Sanction::CATEGORIES)],
            'mode_declenchement' => ['required', Rule::in(Sanction::MODES_DECLENCHEMENT)],
            'statut_declencheur' => [
                Rule::requiredIf($declenchementParSeuil),
                'nullable',
                Rule::in(Sanction::STATUTS_DECLENCHEURS),
            ],
            'seuil' => [Rule::requiredIf($declenchementParSeuil), 'nullable', 'integer', 'min:1', 'max:1000'],
            'periode_calcul' => [
                Rule::requiredIf($declenchementParSeuil),
                'nullable',
                Rule::in(Sanction::PERIODES_CALCUL),
            ],
            'niveau_gravite' => ['required', Rule::in(Sanction::NIVEAUX_GRAVITE)],
            'type_effet' => ['required', Rule::in(Sanction::TYPES_EFFET)],
            'valeur_effet' => [Rule::requiredIf($pointsEnMoins), 'nullable', 'numeric', 'min:0.01', 'max:10000'],
            'active' => ['nullable', 'boolean'],
            'visible_parent_defaut' => ['nullable', 'boolean'],
        ]);

        if (! $declenchementParSeuil) {
            $validated['statut_declencheur'] = 'tous';
            $validated['seuil'] = null;
            $validated['periode_calcul'] = null;
        }

        if (! $pointsEnMoins) {
            $validated['valeur_effet'] = null;
        }

        $validated['active'] = $request->boolean('active');
        $validated['visible_parent_defaut'] = $request->boolean('visible_parent_defaut');

        return $validated;
    }
}
