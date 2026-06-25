<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\DemandeReinscription;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Services\ParentAccessService;
use App\Services\ParentReinscriptionService;
use Illuminate\Http\Request;
use RuntimeException;

class ParentDemandeReinscriptionController extends Controller
{
    public function __construct(
        private ParentAccessService $parentAccessService,
        private ParentReinscriptionService $reinscriptionService
    ) {}

    public function store(Request $request, Eleve $eleve)
    {
        $parent = auth()->user();
        $this->parentAccessService->assertCanAccessEleve($parent, $eleve);

        $validated = $request->validate([
            'ancienne_inscription_id' => ['nullable', 'exists:inscriptions,id'],
            'classe_demandee_id' => ['required', 'exists:classes,id'],
            'commentaire_parent' => ['nullable', 'string', 'max:2000'],
        ]);

        $classeDemandee = Classe::with('anneeScolaire')
            ->findOrFail($validated['classe_demandee_id']);
        $inscription = null;
        $estPremiereInscription = ! Inscription::query()->where('eleve_id', $eleve->id)->exists();

        try {
            if ($estPremiereInscription) {
                $option = $this->reinscriptionService->verifierClassePremiereInscription($eleve, $classeDemandee);
            } else {
                $inscription = Inscription::with(['eleve', 'classe', 'anneeScolaire', 'paiements'])
                    ->where('eleve_id', $eleve->id)
                    ->findOrFail($validated['ancienne_inscription_id'] ?? null);
                $option = $this->reinscriptionService->verifierClasseAutorisee($inscription, $classeDemandee);
            }
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'reinscription' => $exception->getMessage(),
            ]);
        }

        $demande = DemandeReinscription::create([
            'eleve_id' => $eleve->id,
            'parent_id' => $parent->id,
            'ancienne_inscription_id' => $inscription?->id,
            'ancienne_classe_id' => $inscription?->classe_id,
            'nouvelle_annee_scolaire_id' => $option['nouvelle_annee']->id,
            'classe_demandee_id' => $classeDemandee->id,
            'type_demande' => $option['type_demande'],
            'decision_systeme' => $option['decision_systeme'],
            'statut' => DemandeReinscription::STATUT_EN_ATTENTE,
            'commentaire_parent' => $validated['commentaire_parent'] ?? null,
        ]);

        $message = ($demande->type_demande === DemandeReinscription::TYPE_PREMIERE_INSCRIPTION)
            ? 'Demande de première inscription envoyée. Elle attend la validation du gestionnaire.'
            : 'Demande de réinscription envoyée. Elle attend la validation du gestionnaire.';

        return redirect()
            ->route('parent.eleves.show', $eleve)
            ->with('success', $message);
    }
}
