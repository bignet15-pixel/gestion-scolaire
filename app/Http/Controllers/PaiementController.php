<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AnneeScolaire;
use App\Models\Classe;

class PaiementController extends Controller
{
    /**
     * Affiche la liste des paiements avec filtres année / classe
     * et recherche ciblée élève / parent.
     */
    public function index(Request $request)
    {
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $selectedClasseId = $request->input('classe_id');
        $search = trim($request->input('q', ''));

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $classes = Classe::with('anneeScolaire')
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->orderBy('niveau')
            ->orderBy('nom')
            ->get();

        $paiements = Paiement::with([
                'inscription.eleve',
                'inscription.classe.anneeScolaire',
                'inscription.anneeScolaire',
                'gestionnaire',
            ])
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->whereHas('inscription', function ($q) use ($selectedAnneeId) {
                    $q->where('annee_scolaire_id', $selectedAnneeId);
                });
            })
            ->when($selectedClasseId, function ($query) use ($selectedClasseId) {
                $query->whereHas('inscription', function ($q) use ($selectedClasseId) {
                    $q->where('classe_id', $selectedClasseId);
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('inscription.eleve', function ($q) use ($search) {
                    $q->where('matricule', 'like', '%' . $search . '%')
                        ->orWhere('nom', 'like', '%' . $search . '%')
                        ->orWhere('prenom', 'like', '%' . $search . '%')
                        ->orWhere('contact_parent', 'like', '%' . $search . '%')
                        ->orWhereRaw("CONCAT(nom, ' ', prenom) LIKE ?", ['%' . $search . '%'])
                        ->orWhereRaw("CONCAT(prenom, ' ', nom) LIKE ?", ['%' . $search . '%']);
                });
            })
            ->orderByDesc('date_paiement')
            ->orderByDesc('created_at')
            ->get();

        return view('paiements.index', compact(
            'paiements',
            'annees',
            'classes',
            'selectedAnneeId',
            'selectedClasseId',
            'search'
        ));
    }

    /**
     * Affiche le formulaire de création avec recherche ciblée d'inscription.
     */
    public function create(Request $request)
    {
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $selectedClasseId = $request->input('classe_id');
        $selectedInscriptionId = $request->input('inscription_id');
        $search = trim($request->input('q', ''));

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $classes = Classe::with('anneeScolaire')
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->orderBy('niveau')
            ->orderBy('nom')
            ->get();

        $inscriptions = Inscription::with([
                'eleve',
                'classe.anneeScolaire',
                'anneeScolaire',
                'paiements',
            ])
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->when($selectedClasseId, function ($query) use ($selectedClasseId) {
                $query->where('classe_id', $selectedClasseId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('eleve', function ($q) use ($search) {
                    $q->where('matricule', 'like', '%' . $search . '%')
                        ->orWhere('nom', 'like', '%' . $search . '%')
                        ->orWhere('prenom', 'like', '%' . $search . '%')
                        ->orWhere('contact_parent', 'like', '%' . $search . '%')
                        ->orWhereRaw("CONCAT(nom, ' ', prenom) LIKE ?", ['%' . $search . '%'])
                        ->orWhereRaw("CONCAT(prenom, ' ', nom) LIKE ?", ['%' . $search . '%']);
                });
            })
            ->where('statut', 'actif')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->select('inscriptions.*')
            ->get();

        return view('paiements.create', compact(
            'inscriptions',
            'annees',
            'classes',
            'selectedAnneeId',
            'selectedClasseId',
            'selectedInscriptionId',
            'search'
        ));
    }
    /**
     * Enregistre un paiement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inscription_id' => ['required', 'exists:inscriptions,id'],
            'montant' => ['required', 'numeric', 'min:1'],
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['required', 'in:especes,mobile_money,virement,autre'],
        ]);

        $inscription = Inscription::with(['eleve', 'paiements'])
            ->findOrFail($validated['inscription_id']);

        if ($validated['montant'] > $inscription->resteAPayer()) {
            return back()
                ->withErrors([
                    'montant' => 'Le montant ne peut pas dépasser le reste à payer.',
                ])
                ->withInput();
        }

        Paiement::create([
            'inscription_id' => $inscription->id,
            'user_id' => Auth::id(),
            'numero_paiement' => $this->genererNumeroPaiement(),
            'montant' => $validated['montant'],
            'date_paiement' => $validated['date_paiement'],
            'mode_paiement' => $validated['mode_paiement'],

            // Snapshot pour garder les contacts exacts au moment du paiement.
            'contact_parent' => $inscription->eleve?->contact_parent,
            'contact_gestionnaire' => Auth::user()?->phone,
        ]);

        return redirect()
            ->route('paiements.index')
            ->with('success', 'Paiement enregistré avec succès.');
    }

    /**
     * Affiche le détail d’un paiement.
     */
    public function show(Paiement $paiement)
    {
        $paiement->load([
            'inscription.eleve',
            'inscription.classe',
            'inscription.anneeScolaire',
            'gestionnaire',
        ]);

        return view('paiements.show', compact('paiement'));
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(Paiement $paiement)
    {
        $paiement->load('inscription.eleve');

        $inscriptions = Inscription::with([
                'eleve',
                'classe',
                'anneeScolaire',
                'paiements',
            ])
            ->orderByDesc('date_inscription')
            ->get();

        return view('paiements.edit', compact('paiement', 'inscriptions'));
    }

    /**
     * Met à jour un paiement.
     */
    public function update(Request $request, Paiement $paiement)
    {
        $validated = $request->validate([
            'inscription_id' => ['required', 'exists:inscriptions,id'],
            'montant' => ['required', 'numeric', 'min:1'],
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['required', 'in:especes,mobile_money,virement,autre'],
        ]);

        $inscription = Inscription::with(['eleve', 'paiements'])
            ->findOrFail($validated['inscription_id']);

        $totalPayeSansCePaiement = Paiement::where('inscription_id', $inscription->id)
            ->where('id', '!=', $paiement->id)
            ->sum('montant');

        $restePossible = $inscription->frais_attendu - $totalPayeSansCePaiement;

        if ($validated['montant'] > $restePossible) {
            return back()
                ->withErrors([
                    'montant' => 'Le montant dépasse le reste à payer possible.',
                ])
                ->withInput();
        }

        $paiement->update([
            'inscription_id' => $inscription->id,
            'montant' => $validated['montant'],
            'date_paiement' => $validated['date_paiement'],
            'mode_paiement' => $validated['mode_paiement'],
            'contact_parent' => $inscription->eleve?->contact_parent,
            'contact_gestionnaire' => Auth::user()?->phone,
        ]);

        return redirect()
            ->route('paiements.index')
            ->with('success', 'Paiement modifié avec succès.');
    }

    /**
     * Supprime logiquement un paiement.
     */
    public function destroy(Paiement $paiement)
    {
        $paiement->update([
            'is_deleted' => true,
        ]);

        $paiement->delete();

        return redirect()
            ->route('paiements.index')
            ->with('success', 'Paiement supprimé avec succès.');
    }

    /**
     * Génère automatiquement le numéro de paiement / reçu.
     */
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

        /**
     * Génère le reçu PDF d'un paiement.
     */
    public function recu(Paiement $paiement)
    {
        $paiement->load([
            'inscription.eleve',
            'inscription.classe',
            'inscription.anneeScolaire',
            'gestionnaire',
        ]);

        $pdf = Pdf::loadView('pdf.recu_paiement', [
            'paiement' => $paiement,
        ]);

        return $pdf->download('recu-' . $paiement->numero_paiement . '.pdf');
    }
}