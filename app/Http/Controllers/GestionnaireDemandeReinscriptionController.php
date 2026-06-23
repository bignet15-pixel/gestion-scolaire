<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\DemandeReinscription;
use App\Models\Inscription;
use App\Services\NotificationScolaireService;
use App\Services\ParentReinscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GestionnaireDemandeReinscriptionController extends Controller
{
    public function __construct(
        private ParentReinscriptionService $reinscriptionService
    ) {}

    public function index(Request $request)
    {
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $selectedStatut = $request->input('statut');
        $search = trim((string) $request->input('q', ''));
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $demandes = DemandeReinscription::with([
                'eleve',
                'parent',
                'ancienneClasse',
                'classeDemandee',
                'nouvelleAnneeScolaire',
                'ancienneInscription.anneeScolaire',
                'inscriptionCreee',
                'validePar',
            ])
            ->when($selectedAnneeId, fn ($query) => $query->where('nouvelle_annee_scolaire_id', $selectedAnneeId))
            ->when($selectedStatut, fn ($query) => $query->where('statut', $selectedStatut))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('eleve', function ($q) use ($search) {
                    $q->where('matricule', 'like', '%'.$search.'%')
                        ->orWhere('nom', 'like', '%'.$search.'%')
                        ->orWhere('prenom', 'like', '%'.$search.'%')
                        ->orWhereRaw("CONCAT(nom, ' ', prenom) LIKE ?", ['%'.$search.'%'])
                        ->orWhereRaw("CONCAT(prenom, ' ', nom) LIKE ?", ['%'.$search.'%']);
                });
            })
            ->orderByRaw("CASE statut WHEN 'en_attente' THEN 1 WHEN 'validee' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        return view('gestionnaire.demandes_reinscription.index', compact(
            'demandes',
            'annees',
            'selectedAnneeId',
            'selectedStatut',
            'search'
        ));
    }


    public function show(DemandeReinscription $demande)
    {
        $demande->loadMissing([
            'eleve',
            'parent',
            'ancienneClasse',
            'classeDemandee',
            'nouvelleAnneeScolaire',
            'ancienneInscription.anneeScolaire',
            'inscriptionCreee',
            'validePar',
        ]);

        return view('gestionnaire.demandes_reinscription.show', compact('demande'));
    }

    public function valider(Request $request, DemandeReinscription $demande, NotificationScolaireService $notificationScolaireService)
    {
        $validated = $request->validate([
            'commentaire_gestionnaire' => ['nullable', 'string', 'max:3000'],
        ]);

        $demande->loadMissing([
            'ancienneInscription.classe',
            'classeDemandee',
            'eleve',
        ]);

        abort_unless($demande->estEnAttente(), 422, 'Cette demande est déjà traitée.');

        try {
            $this->reinscriptionService->verifierClasseAutorisee(
                $demande->ancienneInscription,
                $demande->classeDemandee
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'demande' => $exception->getMessage(),
            ]);
        }

        $inscriptionExistante = Inscription::query()
            ->where('eleve_id', $demande->eleve_id)
            ->where('annee_scolaire_id', $demande->nouvelle_annee_scolaire_id)
            ->exists();

        if ($inscriptionExistante) {
            throw ValidationException::withMessages([
                'demande' => 'Cet élève possède déjà une inscription pour cette année scolaire.',
            ]);
        }

        DB::transaction(function () use ($demande, $validated) {
            $inscription = Inscription::create([
                'eleve_id' => $demande->eleve_id,
                'classe_id' => $demande->classe_demandee_id,
                'annee_scolaire_id' => $demande->nouvelle_annee_scolaire_id,
                'date_inscription' => now()->toDateString(),
                'frais_attendu' => $demande->classeDemandee?->frais_scolarite ?? 0,
                'statut' => 'actif',
                'is_deleted' => false,
            ]);

            $demande->update([
                'statut' => DemandeReinscription::STATUT_VALIDEE,
                'inscription_creee_id' => $inscription->id,
                'valide_par' => auth()->id(),
                'valide_le' => now(),
                'commentaire_gestionnaire' => $validated['commentaire_gestionnaire'] ?? null,
            ]);
        });

        $notificationScolaireService->notifierDemandeReinscriptionTraitee($demande->fresh([
            'eleve.parents',
            'parent',
            'classeDemandee',
            'nouvelleAnneeScolaire',
        ]));

        return back()->with('success', 'Demande de réinscription validée et inscription officielle créée.');
    }

    public function refuser(Request $request, DemandeReinscription $demande, NotificationScolaireService $notificationScolaireService)
    {
        $validated = $request->validate([
            'commentaire_gestionnaire' => ['required', 'string', 'max:3000'],
        ]);

        abort_unless($demande->estEnAttente(), 422, 'Cette demande est déjà traitée.');

        $demande->update([
            'statut' => DemandeReinscription::STATUT_REFUSEE,
            'valide_par' => auth()->id(),
            'valide_le' => now(),
            'commentaire_gestionnaire' => $validated['commentaire_gestionnaire'],
        ]);

        $notificationScolaireService->notifierDemandeReinscriptionTraitee($demande->fresh([
            'eleve.parents',
            'parent',
            'classeDemandee',
            'nouvelleAnneeScolaire',
        ]));

        return back()->with('success', 'Demande de réinscription refusée.');
    }
}
