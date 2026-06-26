<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\AbsenceRetard;
use App\Models\AnneeScolaire;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\PaiementDeclare;
use App\Models\SanctionAppliquee;
use App\Models\Trimestre;
use App\Models\User;
use App\Services\BulletinService;
use App\Services\ParentAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class EnfantController extends Controller
{
    public function __construct(
        private ParentAccessService $parentAccessService,
        private BulletinService $bulletinService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $parent = $request->user();
        $anneeActive = $this->anneeScolaireCourante();
        $anneeSelectionnee = $this->resolveAnneeSelectionnee($request, $anneeActive);

        $enfantsQuery = $parent->enfants();

        if ($anneeSelectionnee) {
            $enfantsQuery->where(function ($query) use ($anneeSelectionnee) {
                $query->whereHas('inscriptions', function ($q) use ($anneeSelectionnee) {
                    $q->where('annee_scolaire_id', $anneeSelectionnee->id);
                })->orWhereDoesntHave('inscriptions');
            });
        }

        $enfants = $enfantsQuery
            ->with([
                'inscriptions' => function ($query) use ($anneeSelectionnee) {
                    $query->with(['classe.anneeScolaire', 'anneeScolaire', 'paiements'])
                        ->when($anneeSelectionnee, fn ($q) => $q->where('annee_scolaire_id', $anneeSelectionnee->id))
                        ->orderByDesc('date_inscription');
                },
            ])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return $this->success('Liste des enfants récupérée avec succès.', [
            'annee_active' => $this->formatAnnee($anneeActive),
            'annee_selectionnee' => $this->formatAnnee($anneeSelectionnee),
            'total' => $enfants->count(),
            'enfants' => $enfants->map(fn (Eleve $eleve) => $this->formatEnfantResume($eleve))->values(),
        ]);
    }

    public function filtres(Request $request): JsonResponse
    {
        $parent = $request->user();
        $anneeActive = $this->anneeScolaireCourante();

        $annees = AnneeScolaire::query()
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->get();

        $anneeParDefaut = $annees->firstWhere('id', $anneeActive?->id) ?? $annees->first();

        $enfantIdsParAnnee = [];
        $eleveIds = $parent->enfants()->pluck('eleves.id');

        Inscription::query()
            ->whereIn('eleve_id', $eleveIds)
            ->select(['eleve_id', 'annee_scolaire_id'])
            ->get()
            ->each(function (Inscription $inscription) use (&$enfantIdsParAnnee) {
                if (! $inscription->annee_scolaire_id) {
                    return;
                }

                $key = (string) $inscription->annee_scolaire_id;
                $enfantIdsParAnnee[$key] ??= [];
                $enfantIdsParAnnee[$key][] = $inscription->eleve_id;
                $enfantIdsParAnnee[$key] = array_values(array_unique($enfantIdsParAnnee[$key]));
            });

        return $this->success('Filtres parent récupérés avec succès.', [
            'annee_active' => $this->formatAnnee($anneeActive),
            'annee_par_defaut' => $this->formatAnnee($anneeParDefaut),
            'annees_scolaires' => $annees
                ->map(fn (AnneeScolaire $annee) => array_merge(
                    $this->formatAnnee($annee),
                    [
                        'active' => $anneeActive?->id === $annee->id,
                        'selectionnee_par_defaut' => $anneeParDefaut?->id === $annee->id,
                        'enfant_ids' => $enfantIdsParAnnee[(string) $annee->id] ?? [],
                    ]
                ))
                ->values(),
        ]);
    }

    public function dashboard(Request $request, Eleve $eleve): JsonResponse
    {
        $parent = $request->user();
        $this->parentAccessService->assertCanAccessEleve($parent, $eleve);

        $validated = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annee_scolaires,id'],
            'trimestre_id' => ['nullable', 'integer', 'exists:trimestres,id'],
        ]);

        $anneeActive = $this->anneeScolaireCourante();
        $anneeSelectionnee = $this->resolveAnneeSelectionnee($request, $anneeActive);
        $trimestreActif = $this->trimestreActif($anneeSelectionnee);
        $trimestreSelectionne = $this->resolveTrimestreSelectionne(
            $validated['trimestre_id'] ?? null,
            $anneeSelectionnee,
            $trimestreActif
        );

        $eleve->load([
            'parents:id,nom,prenom,email,phone',
            'inscriptions' => function ($query) {
                $query->with(['classe.anneeScolaire', 'anneeScolaire', 'paiements'])
                    ->orderByDesc('date_inscription');
            },
        ]);

        $inscription = $this->inscriptionReference($eleve, $anneeSelectionnee);

        if (! $inscription) {
            return $this->success('Dashboard enfant récupéré avec succès.', [
                'eleve' => $this->formatEleve($eleve),
                'annee_active' => $this->formatAnnee($anneeActive),
                'annee_selectionnee' => $this->formatAnnee($anneeSelectionnee),
                'trimestre_actif' => $this->formatTrimestre($trimestreActif),
                'trimestre_selectionne' => $this->formatTrimestre($trimestreSelectionne),
                'inscription' => null,
                'message' => 'Aucune inscription trouvée pour cet enfant sur cette année scolaire.',
            ]);
        }

        $inscriptionIds = collect([$inscription->id]);
        $trimestreReference = $trimestreSelectionne
            ?: $this->trimestreResultatReference($inscription, $anneeSelectionnee, $trimestreActif);

        $notesRecentes = Note::with(['evaluation.matiere', 'evaluation.trimestre'])
            ->whereIn('inscription_id', $inscriptionIds)
            ->whereNotNull('valeur')
            ->whereHas('evaluation')
            ->when($trimestreSelectionne, function ($query) use ($trimestreSelectionne) {
                $query->whereHas('evaluation', fn ($q) => $q->where('trimestre_id', $trimestreSelectionne->id));
            })
            ->orderByDesc(
                \App\Models\Evaluation::select('date_evaluation')
                    ->whereColumn('evaluations.id', 'notes.evaluation_id')
                    ->limit(1)
            )
            ->limit(8)
            ->get();

        $paiements = Paiement::with('gestionnaire')
            ->where('inscription_id', $inscription->id)
            ->orderByDesc('date_paiement')
            ->limit(8)
            ->get();

        $paiementsDeclares = PaiementDeclare::with(['paiement', 'validePar'])
            ->where('inscription_id', $inscription->id)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $absencesRetards = AbsenceRetard::with('justificationParentale')
            ->where('inscription_id', $inscription->id)
            ->where('visible_parent', true)
            ->orderByDesc('date_debut')
            ->limit(8)
            ->get();

        $sanctions = SanctionAppliquee::with(['sanction', 'trimestre'])
            ->where('inscription_id', $inscription->id)
            ->where('visible_parent', true)
            ->when($trimestreSelectionne, fn ($query) => $query->where('trimestre_id', $trimestreSelectionne->id))
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return $this->success('Dashboard enfant récupéré avec succès.', [
            'eleve' => $this->formatEleve($eleve),
            'annee_active' => $this->formatAnnee($anneeActive),
            'annee_selectionnee' => $this->formatAnnee($anneeSelectionnee),
            'trimestre_actif' => $this->formatTrimestre($trimestreActif),
            'trimestre_selectionne' => $this->formatTrimestre($trimestreSelectionne),
            'inscription' => $this->formatInscription($inscription),
            'situation_financiere' => $this->formatSituationFinanciere($inscription),
            'resultat_reference' => $this->formatResultatReference($inscription, $trimestreReference),
            'dernieres_notes' => $notesRecentes->map(fn (Note $note) => $this->formatNote($note))->values(),
            'paiements_recents' => $paiements->map(fn (Paiement $paiement) => $this->formatPaiement($paiement))->values(),
            'paiements_declares_recents' => $paiementsDeclares->map(fn (PaiementDeclare $paiementDeclare) => $this->formatPaiementDeclare($paiementDeclare))->values(),
            'absences_retards_recents' => $absencesRetards->map(fn (AbsenceRetard $absenceRetard) => $this->formatAbsenceRetard($absenceRetard))->values(),
            'sanctions_recentes' => $sanctions->map(fn (SanctionAppliquee $sanction) => $this->formatSanction($sanction))->values(),
        ]);
    }

    private function resolveAnneeSelectionnee(Request $request, ?AnneeScolaire $anneeActive): ?AnneeScolaire
    {
        $anneeId = $request->integer('annee_scolaire_id');

        if ($anneeId > 0) {
            return AnneeScolaire::find($anneeId) ?? $anneeActive;
        }

        return $anneeActive;
    }

    private function resolveTrimestreSelectionne(?int $trimestreId, ?AnneeScolaire $annee, ?Trimestre $trimestreActif): ?Trimestre
    {
        if ($trimestreId) {
            return Trimestre::query()
                ->when($annee, fn ($query) => $query->where('annee_scolaire_id', $annee->id))
                ->find($trimestreId)
                ?? $trimestreActif;
        }

        return $trimestreActif;
    }

    private function formatEnfantResume(Eleve $eleve): array
    {
        $inscription = $eleve->inscriptions->first();
        $paye = $inscription ? (float) $inscription->paiements->sum('montant') : 0;
        $frais = $inscription ? (float) $inscription->frais_attendu : 0;

        return [
            'id' => $eleve->id,
            'matricule' => $eleve->matricule,
            'nom' => $eleve->nom,
            'prenom' => $eleve->prenom,
            'nom_complet' => $eleve->nomComplet(),
            'sexe' => $eleve->sexe,
            'photo' => $eleve->photo,
            'photo_url' => $this->assetUrl($eleve->photo),
            'lien_parente' => $eleve->pivot?->lien_parente,
            'responsable_principal' => (bool) ($eleve->pivot?->responsable_principal ?? false),
            'inscription_active' => $inscription ? [
                'id' => $inscription->id,
                'classe' => $this->formatClasse($inscription->classe),
                'annee_scolaire' => $this->formatAnnee($inscription->anneeScolaire),
                'frais_attendu' => $frais,
                'total_paye' => $paye,
                'reste_a_payer' => max(0, $frais - $paye),
                'statut' => $inscription->statut,
            ] : null,
        ];
    }

    private function formatEleve(Eleve $eleve): array
    {
        return [
            'id' => $eleve->id,
            'matricule' => $eleve->matricule,
            'nom' => $eleve->nom,
            'prenom' => $eleve->prenom,
            'nom_complet' => $eleve->nomComplet(),
            'sexe' => $eleve->sexe,
            'date_naissance' => $eleve->date_naissance?->toDateString(),
            'lieu_naissance' => $eleve->lieu_naissance,
            'photo' => $eleve->photo,
            'photo_url' => $this->assetUrl($eleve->photo),
        ];
    }

    private function formatInscription(Inscription $inscription): array
    {
        return [
            'id' => $inscription->id,
            'date_inscription' => $inscription->date_inscription?->toDateString(),
            'statut' => $inscription->statut,
            'classe' => $this->formatClasse($inscription->classe),
            'annee_scolaire' => $this->formatAnnee($inscription->anneeScolaire),
        ];
    }

    private function formatSituationFinanciere(Inscription $inscription): array
    {
        $frais = (float) $inscription->frais_attendu;
        $paye = (float) $inscription->paiements->sum('montant');
        $reste = max(0, $frais - $paye);

        return [
            'frais_attendu' => $frais,
            'total_paye' => $paye,
            'reste_a_payer' => $reste,
            'est_solde' => $reste <= 0,
            'taux_paiement' => $frais > 0 ? round(($paye / $frais) * 100, 2) : 0,
        ];
    }

    private function formatResultatReference(Inscription $inscription, ?Trimestre $trimestre): array
    {
        if (! $trimestre) {
            return [
                'disponible' => false,
                'message' => 'Aucun trimestre fermé disponible pour les résultats.',
                'trimestre' => null,
            ];
        }

        try {
            $bulletin = $this->bulletinService->bulletinTrimestriel($inscription, $trimestre);

            return [
                'disponible' => true,
                'message' => 'Résultat trimestriel disponible.',
                'trimestre' => $this->formatTrimestre($trimestre),
                'moyenne_generale' => $bulletin['moyenne_finale'] ?? $bulletin['moyenne'] ?? null,
                'moyenne_avant_sanction' => $bulletin['moyenne_avant_sanction'] ?? null,
                'rang' => $bulletin['rang'] ?? null,
                'effectif' => $bulletin['effectif'] ?? null,
                'appreciation' => $bulletin['appreciation'] ?? null,
                'total_coefficients' => $bulletin['total_coefficients'] ?? null,
                'total_points_en_moins' => $bulletin['total_points_en_moins_visibles'] ?? $bulletin['total_points_en_moins'] ?? 0,
            ];
        } catch (RuntimeException $exception) {
            return [
                'disponible' => false,
                'message' => $exception->getMessage(),
                'trimestre' => $this->formatTrimestre($trimestre),
            ];
        }
    }

    private function formatNote(Note $note): array
    {
        $evaluation = $note->evaluation;

        return [
            'id' => $note->id,
            'valeur' => (float) $note->valeur,
            'appreciation' => $note->appreciation,
            'evaluation' => $evaluation ? [
                'id' => $evaluation->id,
                'nom' => $evaluation->nom,
                'type' => $evaluation->type,
                'bareme' => (float) $evaluation->bareme,
                'coefficient' => (float) $evaluation->coefficient,
                'date_evaluation' => $evaluation->date_evaluation?->toDateString(),
                'matiere' => $evaluation->matiere ? [
                    'id' => $evaluation->matiere->id,
                    'nom' => $evaluation->matiere->nom,
                ] : null,
                'trimestre' => $this->formatTrimestre($evaluation->trimestre),
            ] : null,
        ];
    }

    private function formatPaiement(Paiement $paiement): array
    {
        return [
            'id' => $paiement->id,
            'numero_paiement' => $paiement->numero_paiement,
            'montant' => (float) $paiement->montant,
            'date_paiement' => $paiement->date_paiement?->toDateString(),
            'mode_paiement' => $paiement->mode_paiement,
            'gestionnaire' => $paiement->gestionnaire ? [
                'id' => $paiement->gestionnaire->id,
                'nom' => $paiement->gestionnaire->nom,
                'prenom' => $paiement->gestionnaire->prenom,
            ] : null,
        ];
    }

    private function formatPaiementDeclare(PaiementDeclare $paiementDeclare): array
    {
        return [
            'id' => $paiementDeclare->id,
            'montant' => (float) $paiementDeclare->montant,
            'mode_paiement' => $paiementDeclare->mode_paiement,
            'numero_transfert' => $paiementDeclare->numero_transfert,
            'reference_transaction' => $paiementDeclare->reference_transaction,
            'statut' => $paiementDeclare->statut,
            'libelle_statut' => method_exists($paiementDeclare, 'libelleStatut') ? $paiementDeclare->libelleStatut() : $paiementDeclare->statut,
            'date_paiement' => $paiementDeclare->date_paiement?->toDateString(),
            'cree_le' => $paiementDeclare->created_at?->toDateTimeString(),
        ];
    }

    private function formatAbsenceRetard(AbsenceRetard $absenceRetard): array
    {
        return [
            'id' => $absenceRetard->id,
            'type' => $absenceRetard->type,
            'libelle_type' => $absenceRetard->libelleType(),
            'date_debut' => $absenceRetard->date_debut?->toDateString(),
            'date_fin' => $absenceRetard->date_fin?->toDateString(),
            'periode' => $absenceRetard->periode,
            'libelle_periode' => $absenceRetard->libellePeriode(),
            'heure_debut' => $absenceRetard->heure_debut?->format('H:i'),
            'heure_fin' => $absenceRetard->heure_fin?->format('H:i'),
            'heure_arrivee' => $absenceRetard->heure_arrivee?->format('H:i'),
            'duree_minutes' => $absenceRetard->duree_minutes,
            'statut' => $absenceRetard->statut,
            'libelle_statut' => $absenceRetard->libelleStatut(),
            'motif' => $absenceRetard->motif,
            'justification_parentale' => $absenceRetard->justificationParentale ? [
                'id' => $absenceRetard->justificationParentale->id,
                'statut' => $absenceRetard->justificationParentale->statut,
            ] : null,
        ];
    }

    private function formatSanction(SanctionAppliquee $sanction): array
    {
        return [
            'id' => $sanction->id,
            'sanction' => $sanction->sanction ? [
                'id' => $sanction->sanction->id,
                'nom' => $sanction->sanction->nom,
                'type' => $sanction->sanction->type,
            ] : null,
            'trimestre' => $this->formatTrimestre($sanction->trimestre),
            'motif' => $sanction->motif,
            'statut' => $sanction->statut,
            'origine' => $sanction->origine,
            'type_effet' => $sanction->type_effet,
            'valeur_effet' => $sanction->valeur_effet !== null ? (float) $sanction->valeur_effet : null,
            'date_application' => $sanction->date_application?->toDateString(),
            'periode_debut' => $sanction->periode_debut?->toDateString(),
            'periode_fin' => $sanction->periode_fin?->toDateString(),
        ];
    }

    private function formatClasse($classe): ?array
    {
        if (! $classe) {
            return null;
        }

        return [
            'id' => $classe->id,
            'niveau' => $classe->niveau,
            'nom' => $classe->nom,
            'frais_scolarite' => isset($classe->frais_scolarite) ? (float) $classe->frais_scolarite : null,
        ];
    }

    private function formatAnnee(?AnneeScolaire $annee): ?array
    {
        if (! $annee) {
            return null;
        }

        return [
            'id' => $annee->id,
            'libelle' => $annee->libelle,
            'date_debut' => $annee->date_debut?->toDateString(),
            'date_fin' => $annee->date_fin?->toDateString(),
            'statut' => $annee->statut,
        ];
    }

    private function formatTrimestre(?Trimestre $trimestre): ?array
    {
        if (! $trimestre) {
            return null;
        }

        return [
            'id' => $trimestre->id,
            'nom' => $trimestre->nom,
            'date_debut' => $trimestre->date_debut?->toDateString(),
            'date_fin' => $trimestre->date_fin?->toDateString(),
            'statut' => $trimestre->statut,
        ];
    }

    private function inscriptionReference(Eleve $eleve, ?AnneeScolaire $anneeActive): ?Inscription
    {
        return $eleve->inscriptions
            ->when($anneeActive, fn ($collection) => $collection->where('annee_scolaire_id', $anneeActive->id))
            ->sortByDesc('date_inscription')
            ->first()
            ?? $eleve->inscriptions->sortByDesc('date_inscription')->first();
    }

    private function trimestreResultatReference(Inscription $inscription, ?AnneeScolaire $anneeActive, ?Trimestre $trimestreActif): ?Trimestre
    {
        if ($trimestreActif?->estFerme()) {
            return $trimestreActif;
        }

        return Trimestre::where('annee_scolaire_id', $inscription->annee_scolaire_id)
            ->where('statut', 'ferme')
            ->orderByDesc('date_fin')
            ->first();
    }

    private function trimestreActif(?AnneeScolaire $annee): ?Trimestre
    {
        if (! $annee) {
            return null;
        }

        return Trimestre::where('annee_scolaire_id', $annee->id)
            ->where('statut', 'actif')
            ->orderBy('date_debut')
            ->first()
            ?? Trimestre::where('annee_scolaire_id', $annee->id)
                ->orderBy('date_debut')
                ->first();
    }

    private function anneeScolaireCourante(): ?AnneeScolaire
    {
        return AnneeScolaire::where('statut', 'active')
            ->orderByDesc('date_debut')
            ->first()
            ?? AnneeScolaire::orderByDesc('date_debut')->first();
    }

    private function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url('/storage/' . ltrim($path, '/'));
    }

    private function success(string $message, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
