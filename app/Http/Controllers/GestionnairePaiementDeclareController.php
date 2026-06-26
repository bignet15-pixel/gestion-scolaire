<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Paiement;
use App\Models\PaiementDeclare;
use App\Services\NotificationScolaireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GestionnairePaiementDeclareController extends Controller
{
    public function index(Request $request)
    {
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $selectedStatut = $request->input('statut');
        $search = trim((string) $request->input('q', ''));
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $paiementsDeclares = PaiementDeclare::with([
                'inscription.eleve',
                'inscription.classe.anneeScolaire',
                'parent',
                'validePar',
                'paiement',
            ])
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->whereHas('inscription', function ($q) use ($selectedAnneeId) {
                    $q->where('annee_scolaire_id', $selectedAnneeId);
                });
            })
            ->when($selectedStatut, fn ($query) => $query->where('statut', $selectedStatut))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('inscription.eleve', function ($q) use ($search) {
                    $q->where('matricule', 'like', '%'.$search.'%')
                        ->orWhere('nom', 'like', '%'.$search.'%')
                        ->orWhere('prenom', 'like', '%'.$search.'%')
                        ->orWhereRaw("CONCAT(nom, ' ', prenom) LIKE ?", ['%'.$search.'%'])
                        ->orWhereRaw("CONCAT(prenom, ' ', nom) LIKE ?", ['%'.$search.'%']);
                });
            })
            ->orderByRaw("CASE statut WHEN 'en_attente' THEN 1 WHEN 'valide' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        return view('gestionnaire.paiements_declares.index', compact(
            'paiementsDeclares',
            'annees',
            'selectedAnneeId',
            'selectedStatut',
            'search'
        ));
    }


    public function show(PaiementDeclare $paiementDeclare)
    {
        $paiementDeclare->loadMissing([
            'inscription.eleve',
            'inscription.classe.anneeScolaire',
            'parent',
            'validePar',
            'paiement',
        ]);

        return view('gestionnaire.paiements_declares.show', compact('paiementDeclare'));
    }

    public function valider(Request $request, PaiementDeclare $paiementDeclare, NotificationScolaireService $notificationScolaireService)
    {
        $validated = $request->validate([
            'commentaire_validation' => ['nullable', 'string', 'max:3000'],
        ]);

        $paiementDeclare->loadMissing(['inscription.eleve', 'inscription.paiements']);

        abort_unless($paiementDeclare->estEnAttente(), 422, 'Cette déclaration est déjà traitée.');

        if ((float) $paiementDeclare->montant > $paiementDeclare->inscription->resteAPayer()) {
            throw ValidationException::withMessages([
                'montant' => 'Le montant déclaré dépasse le reste à payer actuel.',
            ]);
        }

        DB::transaction(function () use ($paiementDeclare, $validated) {
            $paiement = Paiement::create([
                'inscription_id' => $paiementDeclare->inscription_id,
                'user_id' => auth()->id(),
                'numero_paiement' => $this->genererNumeroPaiement(),
                'montant' => $paiementDeclare->montant,
                'date_paiement' => now()->toDateString(),
                'mode_paiement' => in_array($paiementDeclare->mode_paiement, ['especes', 'mobile_money', 'virement', 'autre'], true)
                    ? $paiementDeclare->mode_paiement
                    : 'autre',
                'contact_parent' => $paiementDeclare->numero_transfert ?: $paiementDeclare->inscription->eleve?->contact_parent,
                'contact_gestionnaire' => auth()->user()?->phone,
            ]);

            $paiementDeclare->update([
                'statut' => PaiementDeclare::STATUT_VALIDE,
                'valide_par' => auth()->id(),
                'valide_le' => now(),
                'paiement_id' => $paiement->id,
                'commentaire_validation' => $validated['commentaire_validation'] ?? null,
            ]);
        });

        $notificationScolaireService->notifierPaiementDeclareTraite($paiementDeclare->fresh([
            'inscription.eleve.parents',
            'parent',
            'paiement',
        ]));

        return back()->with('success', 'Paiement déclaré validé et paiement officiel créé.');
    }

    public function refuser(Request $request, PaiementDeclare $paiementDeclare, NotificationScolaireService $notificationScolaireService)
    {
        $validated = $request->validate([
            'commentaire_validation' => ['required', 'string', 'max:3000'],
        ]);

        abort_unless($paiementDeclare->estEnAttente(), 422, 'Cette déclaration est déjà traitée.');

        $paiementDeclare->update([
            'statut' => PaiementDeclare::STATUT_REFUSE,
            'valide_par' => auth()->id(),
            'valide_le' => now(),
            'commentaire_validation' => $validated['commentaire_validation'],
        ]);

        $notificationScolaireService->notifierPaiementDeclareTraite($paiementDeclare->fresh([
            'inscription.eleve.parents',
            'parent',
            'paiement',
        ]));

        return back()->with('success', 'Paiement déclaré refusé.');
    }

    public function preuve(PaiementDeclare $paiementDeclare)
    {
        abort_unless($paiementDeclare->preuve_paiement, 404);

        return Storage::disk('public')->response($paiementDeclare->preuve_paiement);
    }

    private function genererNumeroPaiement(): string
    {
        $annee = date('Y');

        $dernier = Paiement::where('numero_paiement', 'like', 'REC-' . $annee . '-%')
            ->orderByDesc('id')
            ->first();

        if (! $dernier) {
            return 'REC-' . $annee . '-0001';
        }

        $numero = (int) str_replace('REC-' . $annee . '-', '', $dernier->numero_paiement);
        $numero++;

        return 'REC-' . $annee . '-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}
