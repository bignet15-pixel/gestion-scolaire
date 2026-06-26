<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\PaiementDeclare;
use App\Services\ParentAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParentPaiementDeclareController extends Controller
{
    public function __construct(
        private ParentAccessService $parentAccessService
    ) {}

    public function index(Request $request)
    {
        $parent = auth()->user();
        $enfantIds = $parent->enfants()->pluck('eleves.id');
        $selectedEnfantId = $request->input('eleve_id');
        $selectedStatut = $request->input('statut');

        $enfants = $parent->enfants()
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $paiementsDeclares = PaiementDeclare::with([
                'inscription.eleve',
                'inscription.classe.anneeScolaire',
                'paiement',
            ])
            ->whereHas('inscription', function ($query) use ($enfantIds, $selectedEnfantId) {
                $query->whereIn('eleve_id', $enfantIds)
                    ->when($selectedEnfantId, fn ($q) => $q->where('eleve_id', $selectedEnfantId));
            })
            ->when($selectedStatut, fn ($query) => $query->where('statut', $selectedStatut))
            ->orderByDesc('created_at')
            ->get();

        return view('parent.paiements_declares.index', compact(
            'enfants',
            'paiementsDeclares',
            'selectedEnfantId',
            'selectedStatut'
        ));
    }

    /**
     * Déclaration parentale : aucun paiement officiel n'est créé ici.
     */
    public function store(Request $request, Inscription $inscription)
    {
        $parent = auth()->user();
        $inscription->loadMissing(['eleve', 'classe', 'anneeScolaire', 'paiements']);
        $this->parentAccessService->assertCanAccessInscription($parent, $inscription);

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'mode_paiement' => ['required', Rule::in(PaiementDeclare::MODES_PAIEMENT)],
            'numero_transfert' => [Rule::requiredIf(fn () => $request->input('mode_paiement') !== 'especes'), 'nullable', 'string', 'max:50'],
            'reference_transaction' => [Rule::requiredIf(fn () => $request->input('mode_paiement') !== 'especes'), 'nullable', 'string', 'max:190'],
            'preuve_paiement' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $totalEnAttente = (float) PaiementDeclare::query()
            ->where('inscription_id', $inscription->id)
            ->where('statut', PaiementDeclare::STATUT_EN_ATTENTE)
            ->sum('montant');

        $resteDeclarable = max(0, $inscription->resteAPayer() - $totalEnAttente);

        if ((float) $validated['montant'] > $resteDeclarable) {
            throw ValidationException::withMessages([
                'montant' => 'Le montant déclaré dépasse le reste encore déclarable pour cette inscription.',
            ]);
        }

        if ($request->hasFile('preuve_paiement')) {
            $validated['preuve_paiement'] = $request
                ->file('preuve_paiement')
                ->store('parent/paiements', 'public');
        }

        PaiementDeclare::create([
            'inscription_id' => $inscription->id,
            'parent_id' => $parent->id,
            'montant' => $validated['montant'],
            'mode_paiement' => $validated['mode_paiement'],
            'numero_transfert' => $validated['numero_transfert'] ?? null,
            'reference_transaction' => $validated['reference_transaction'] ?? null,
            'preuve_paiement' => $validated['preuve_paiement'] ?? null,
            'statut' => PaiementDeclare::STATUT_EN_ATTENTE,
        ]);

        return redirect()
            ->route('parent.eleves.show', $inscription->eleve)
            ->with('success', 'Paiement déclaré. Il attend la validation du gestionnaire.');
    }

    public function preuve(PaiementDeclare $paiementDeclare)
    {
        $parent = auth()->user();
        $paiementDeclare->loadMissing('inscription');

        abort_unless(
            $paiementDeclare->preuve_paiement
                && $this->parentAccessService->canAccessInscription($parent, $paiementDeclare->inscription),
            404
        );

        return Storage::disk('public')->response($paiementDeclare->preuve_paiement);
    }
}
