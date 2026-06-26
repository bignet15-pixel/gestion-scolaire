<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\AbsenceRetard;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\DemandeReinscription;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\JustificationAbsenceRetard;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\PaiementDeclare;
use App\Models\SanctionAppliquee;
use App\Models\Trimestre;
use App\Services\BulletinService;
use App\Services\ParentAccessService;
use App\Services\ParentReinscriptionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;


class EnfantDetailController extends Controller
{
    public function __construct(
        private ParentAccessService $parentAccessService,
        private BulletinService $bulletinService,
        private ParentReinscriptionService $reinscriptionService
    ) {}

    public function filtres(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $inscriptions = Inscription::with(['classe.anneeScolaire', 'anneeScolaire'])
            ->where('eleve_id', $eleve->id)
            ->orderByDesc('date_inscription')
            ->get();

        $anneeActive = $this->anneeScolaireCourante();
        $annees = AnneeScolaire::query()
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->get();

        $anneeParDefaut = $annees->firstWhere('id', $anneeActive?->id) ?? $annees->first();
        $anneeIds = $annees->pluck('id')->filter()->values();
        $inscriptionIds = $inscriptions->pluck('id')->filter()->values();
        $classeIds = $inscriptions->pluck('classe_id')->filter()->unique()->values();

        $trimestres = Trimestre::query()
            ->whereIn('annee_scolaire_id', $anneeIds)
            ->orderBy('annee_scolaire_id')
            ->orderBy('date_debut')
            ->get()
            ->map(fn (Trimestre $trimestre) => array_merge(
                $this->formatTrimestre($trimestre),
                ['annee_scolaire_id' => $trimestre->annee_scolaire_id]
            ))
            ->values();

        $matieresDepuisNotes = Note::with('evaluation.matiere')
            ->whereIn('inscription_id', $inscriptionIds)
            ->get()
            ->pluck('evaluation.matiere')
            ->filter();

        $matieresDepuisClasses = ClasseMatiereUser::with('matiere')
            ->whereIn('classe_id', $classeIds)
            ->get()
            ->pluck('matiere')
            ->filter();

        $matieres = $matieresDepuisNotes
            ->concat($matieresDepuisClasses)
            ->filter()
            ->unique('id')
            ->sortBy('nom')
            ->values()
            ->map(fn (Matiere $matiere) => $this->formatMatiere($matiere));

        return $this->success('Filtres historiques récupérés avec succès.', [
            'eleve' => $this->formatEleveSimple($eleve),
            'annee_active' => $this->formatAnnee($anneeActive),
            'annee_par_defaut' => $this->formatAnnee($anneeParDefaut),
            'inscription_reference' => $this->formatInscriptionSimple(
                $inscriptions->firstWhere('annee_scolaire_id', $anneeParDefaut?->id) ?? $inscriptions->first()
            ),
            'inscriptions' => $inscriptions
                ->map(fn (Inscription $inscription) => $this->formatInscriptionSimple($inscription))
                ->values(),
            'annees_scolaires' => $annees
                ->map(fn (AnneeScolaire $annee) => array_merge(
                    $this->formatAnnee($annee),
                    [
                        'active' => $anneeActive?->id === $annee->id,
                        'selectionnee_par_defaut' => $anneeParDefaut?->id === $annee->id,
                    ]
                ))
                ->values(),
            'trimestres' => $trimestres,
            'matieres' => $matieres,
            'statuts' => [
                'paiements_declares' => $this->formatOptions(PaiementDeclare::STATUTS, [
                    PaiementDeclare::STATUT_EN_ATTENTE => 'En attente',
                    PaiementDeclare::STATUT_VALIDE => 'Validé',
                    PaiementDeclare::STATUT_REFUSE => 'Refusé',
                ]),
                'absences_retards' => $this->formatOptions(AbsenceRetard::STATUTS, [
                    'en_attente' => 'En attente',
                    'justifiee' => 'Justifiée',
                    'non_justifiee' => 'Non justifiée',
                    'refusee' => 'Refusée',
                ]),
                'sanctions' => $this->formatOptions(SanctionAppliquee::STATUTS, [
                    'proposee' => 'Proposée',
                    'appliquee' => 'En cours',
                    'terminee' => 'Définitive',
                    'annulee' => 'Annulée',
                    'ignoree' => 'Ignorée',
                ]),
                'demandes_reinscription' => $this->formatOptions(DemandeReinscription::STATUTS, [
                    DemandeReinscription::STATUT_EN_ATTENTE => 'En attente',
                    DemandeReinscription::STATUT_VALIDEE => 'Validée',
                    DemandeReinscription::STATUT_REFUSEE => 'Refusée',
                    DemandeReinscription::STATUT_ANNULEE => 'Annulée',
                ]),
            ],
            'types' => [
                'absences_retards' => $this->formatOptions(AbsenceRetard::TYPES, [
                    'absence' => 'Absence',
                    'retard' => 'Retard',
                ]),
                'paiements_declares_modes' => $this->formatOptions(PaiementDeclare::MODES_PAIEMENT, [
                    'especes' => 'Espèces',
                    'mobile_money' => 'Mobile money',
                    'virement' => 'Virement',
                    'autre' => 'Autre',
                ]),
            ],
        ]);
    }

    public function notes(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annee_scolaires,id'],
            'trimestre_id' => ['nullable', 'integer', 'exists:trimestres,id'],
            'matiere_id' => ['nullable', 'integer', 'exists:matieres,id'],
        ]);

        $contexte = $this->contexteInscriptions($eleve, $validated['annee_scolaire_id'] ?? null, false);
        $inscriptionIds = $contexte['inscriptions']->pluck('id');

        $notes = Note::with([
                'evaluation.matiere',
                'evaluation.trimestre.anneeScolaire',
                'inscription.classe.anneeScolaire',
            ])
            ->whereIn('inscription_id', $inscriptionIds)
            ->whereNotNull('valeur')
            ->when($validated['trimestre_id'] ?? null, function ($query, $trimestreId) {
                $query->whereHas('evaluation', fn ($q) => $q->where('trimestre_id', $trimestreId));
            })
            ->when($validated['matiere_id'] ?? null, function ($query, $matiereId) {
                $query->whereHas('evaluation', fn ($q) => $q->where('matiere_id', $matiereId));
            })
            ->get()
            ->sortByDesc(fn (Note $note) => optional($note->evaluation?->date_evaluation)->timestamp ?? 0)
            ->values()
            ->map(fn (Note $note) => $this->formatNote($note));

        return $this->success('Notes récupérées avec succès.', [
            'eleve' => $this->formatEleveSimple($eleve),
            'annee_selectionnee' => $this->formatAnnee($contexte['annee']),
            'filtres' => [
                'annee_scolaire_id' => $contexte['annee']?->id,
                'trimestre_id' => $validated['trimestre_id'] ?? null,
                'matiere_id' => $validated['matiere_id'] ?? null,
            ],
            'total' => $notes->count(),
            'notes' => $notes,
        ]);
    }

    public function resultats(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annee_scolaires,id'],
        ]);

        $contexte = $this->contexteInscriptions($eleve, $validated['annee_scolaire_id'] ?? null, false);
        $inscription = $contexte['inscription_reference'];
        $trimestres = Trimestre::query()
            ->when($contexte['annee'], fn ($query) => $query->where('annee_scolaire_id', $contexte['annee']->id))
            ->orderBy('date_debut')
            ->get();

        $resultats = $trimestres->map(function (Trimestre $trimestre) use ($inscription) {
            return $this->formatResultatTrimestriel($inscription, $trimestre);
        });

        $bulletinAnnuel = $this->formatBulletinAnnuelDisponible($inscription);

        return $this->success('Résultats récupérés avec succès.', [
            'eleve' => $this->formatEleveSimple($eleve),
            'annee_selectionnee' => $this->formatAnnee($contexte['annee']),
            'inscription_reference' => $this->formatInscriptionSimple($inscription),
            'resultats_trimestriels' => $resultats,
            'bulletin_annuel' => $bulletinAnnuel,
        ]);
    }

    public function bulletins(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annee_scolaires,id'],
        ]);

        $contexte = $this->contexteInscriptions($eleve, $validated['annee_scolaire_id'] ?? null, false);
        $inscription = $contexte['inscription_reference'];
        $trimestres = Trimestre::query()
            ->when($contexte['annee'], fn ($query) => $query->where('annee_scolaire_id', $contexte['annee']->id))
            ->orderBy('date_debut')
            ->get();

        $bulletins = $trimestres->map(function (Trimestre $trimestre) use ($inscription, $eleve) {
            $resultat = $this->formatResultatTrimestriel($inscription, $trimestre);

            return [
                'type' => 'trimestriel',
                'trimestre' => $this->formatTrimestre($trimestre),
                'disponible' => $resultat['disponible'],
                'message' => $resultat['message'],
                'download_url' => $resultat['disponible'] && $inscription
                    ? route('api.parent.enfants.bulletins.trimestriel.telecharger', [$eleve, $trimestre], true)
                    : null,
            ];
        })->values();

        $annuel = $this->formatBulletinAnnuelDisponible($inscription);

        return $this->success('Bulletins récupérés avec succès.', [
            'eleve' => $this->formatEleveSimple($eleve),
            'annee_selectionnee' => $this->formatAnnee($contexte['annee']),
            'inscription_reference' => $this->formatInscriptionSimple($inscription),
            'bulletins_trimestriels' => $bulletins,
            'bulletin_annuel' => $annuel,
        ]);
    }

    public function telechargerBulletinTrimestriel(Request $request, Eleve $eleve, Trimestre $trimestre): Response|JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $inscription = $this->inscriptionPourTrimestre($eleve, $trimestre);

        if (! $inscription) {
            return $this->error('Aucune inscription trouvée pour ce trimestre.', 404);
        }

        try {
            $data = $this->bulletinService->bulletinTrimestriel($inscription, $trimestre);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $nomFichier = 'bulletin-' . $this->slug($inscription->eleve?->matricule ?? 'eleve')
            . '-' . $this->slug($trimestre->nom) . '.pdf';

        return Pdf::loadView('pdf.bulletin_trimestriel', $data)
            ->setPaper('a4', 'portrait')
            ->download($nomFichier);
    }

    public function telechargerBulletinAnnuel(Request $request, Eleve $eleve): Response|JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annee_scolaires,id'],
        ]);

        $contexte = $this->contexteInscriptions($eleve, $validated['annee_scolaire_id'] ?? null, false);
        $inscription = $contexte['inscription_reference'];

        if (! $inscription) {
            return $this->error('Aucune inscription trouvée pour cette année scolaire.', 404);
        }

        try {
            $data = $this->bulletinService->bulletinAnnuel($inscription);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $nomFichier = 'bulletin-annuel-' . $this->slug($inscription->eleve?->matricule ?? 'eleve') . '.pdf';

        return Pdf::loadView('pdf.bulletin_annuel', $data)
            ->setPaper('a4', 'portrait')
            ->download($nomFichier);
    }

    public function paiements(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annee_scolaires,id'],
        ]);

        $contexte = $this->contexteInscriptions($eleve, $validated['annee_scolaire_id'] ?? null, false);
        $inscriptionIds = $contexte['inscriptions']->pluck('id');

        $paiements = Paiement::with(['gestionnaire', 'inscription.classe.anneeScolaire'])
            ->whereIn('inscription_id', $inscriptionIds)
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Paiement $paiement) => $this->formatPaiement($paiement));

        return $this->success('Paiements récupérés avec succès.', [
            'eleve' => $this->formatEleveSimple($eleve),
            'annee_selectionnee' => $this->formatAnnee($contexte['annee']),
            'situation_financiere' => $this->situationFinanciere($contexte['inscriptions']),
            'total' => $paiements->count(),
            'paiements' => $paiements,
        ]);
    }

    public function recuPaiement(Request $request, Paiement $paiement): Response|JsonResponse
    {
        $paiement->load([
            'inscription.eleve',
            'inscription.classe',
            'inscription.anneeScolaire',
            'gestionnaire',
        ]);

        if (! $paiement->inscription) {
            return $this->error('Paiement introuvable.', 404);
        }

        $this->parentAccessService->assertCanAccessInscription($request->user(), $paiement->inscription);

        return Pdf::loadView('pdf.recu_paiement', [
                'paiement' => $paiement,
            ])
            ->setPaper('a4', 'portrait')
            ->download('recu-' . $paiement->numero_paiement . '.pdf');
    }

    public function paiementsDeclares(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annee_scolaires,id'],
            'statut' => ['nullable', Rule::in(PaiementDeclare::STATUTS)],
        ]);

        $contexte = $this->contexteInscriptions($eleve, $validated['annee_scolaire_id'] ?? null, false);
        $inscriptionIds = $contexte['inscriptions']->pluck('id');

        $paiementsDeclares = PaiementDeclare::with([
                'paiement',
                'validePar',
                'inscription.classe.anneeScolaire',
            ])
            ->whereIn('inscription_id', $inscriptionIds)
            ->when($validated['statut'] ?? null, fn ($query, $statut) => $query->where('statut', $statut))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PaiementDeclare $paiementDeclare) => $this->formatPaiementDeclare($paiementDeclare));

        return $this->success('Paiements déclarés récupérés avec succès.', [
            'eleve' => $this->formatEleveSimple($eleve),
            'annee_selectionnee' => $this->formatAnnee($contexte['annee']),
            'total' => $paiementsDeclares->count(),
            'paiements_declares' => $paiementsDeclares,
        ]);
    }

    public function declarerPaiement(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'inscription_id' => ['nullable', 'integer', 'exists:inscriptions,id'],
            'montant' => ['required', 'numeric', 'min:1'],
            'mode_paiement' => ['required', Rule::in(PaiementDeclare::MODES_PAIEMENT)],
            'numero_transfert' => [Rule::requiredIf(fn () => $request->input('mode_paiement') !== 'especes'), 'nullable', 'string', 'max:50'],
            'reference_transaction' => [Rule::requiredIf(fn () => $request->input('mode_paiement') !== 'especes'), 'nullable', 'string', 'max:190'],
            'preuve_paiement' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $inscription = $this->inscriptionAction($eleve, $validated['inscription_id'] ?? null);
        $this->parentAccessService->assertCanAccessInscription($request->user(), $inscription);

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

        $preuvePaiement = null;

        if ($request->hasFile('preuve_paiement')) {
            $preuvePaiement = $request->file('preuve_paiement')->store('parent/paiements', 'public');
        }

        $paiementDeclare = PaiementDeclare::create([
            'inscription_id' => $inscription->id,
            'parent_id' => $request->user()->id,
            'montant' => $validated['montant'],
            'mode_paiement' => $validated['mode_paiement'],
            'numero_transfert' => $validated['numero_transfert'] ?? null,
            'reference_transaction' => $validated['reference_transaction'] ?? null,
            'preuve_paiement' => $preuvePaiement,
            'statut' => PaiementDeclare::STATUT_EN_ATTENTE,
        ]);

        $paiementDeclare->load(['paiement', 'validePar', 'inscription.classe.anneeScolaire']);

        return $this->success('Paiement déclaré. Il attend la validation du gestionnaire.', [
            'paiement_declare' => $this->formatPaiementDeclare($paiementDeclare),
        ], 201);
    }

    public function preuvePaiementDeclare(Request $request, PaiementDeclare $paiementDeclare): Response|JsonResponse
    {
        $paiementDeclare->loadMissing('inscription');

        if (! $paiementDeclare->preuve_paiement
            || ! $paiementDeclare->inscription
            || ! $this->parentAccessService->canAccessInscription($request->user(), $paiementDeclare->inscription)) {
            return $this->error('Preuve de paiement introuvable.', 404);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->response($paiementDeclare->preuve_paiement);
    }

    public function absencesRetards(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annee_scolaires,id'],
            'trimestre_id' => ['nullable', 'integer', 'exists:trimestres,id'],
            'type' => ['nullable', Rule::in(AbsenceRetard::TYPES)],
            'statut' => ['nullable', Rule::in(AbsenceRetard::STATUTS)],
        ]);

        $contexte = $this->contexteInscriptions($eleve, $validated['annee_scolaire_id'] ?? null, false);
        $inscriptionIds = $contexte['inscriptions']->pluck('id');

        $absencesRetards = AbsenceRetard::with([
                'inscription.classe.anneeScolaire',
                'enregistrePar',
                'justificationParentale.parent',
                'justificationParentale.traitePar',
            ])
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('visible_parent', true)
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($validated['statut'] ?? null, fn ($query, $statut) => $query->where('statut', $statut))
            ->when($validated['trimestre_id'] ?? null, function ($query, $trimestreId) {
                $trimestre = Trimestre::find($trimestreId);

                if ($trimestre?->date_debut) {
                    $query->whereDate('date_debut', '>=', $trimestre->date_debut);
                }

                if ($trimestre?->date_fin) {
                    $query->whereDate('date_debut', '<=', $trimestre->date_fin);
                }
            })
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->get()
            ->map(fn (AbsenceRetard $absenceRetard) => $this->formatAbsenceRetard($absenceRetard));

        return $this->success('Absences et retards récupérés avec succès.', [
            'eleve' => $this->formatEleveSimple($eleve),
            'annee_selectionnee' => $this->formatAnnee($contexte['annee']),
            'total' => $absencesRetards->count(),
            'absences_retards' => $absencesRetards,
        ]);
    }

    public function justifierAbsenceRetard(Request $request, AbsenceRetard $absenceRetard): JsonResponse
    {
        $absenceRetard->loadMissing(['inscription.eleve', 'justificationParentale']);
        $this->parentAccessService->assertCanAccessAbsenceRetard($request->user(), $absenceRetard);

        if ($absenceRetard->statut === 'justifiee') {
            return $this->error('Cet événement est déjà marqué comme justifié.', 422);
        }

        if ($absenceRetard->justificationParentale) {
            return $this->error('Une demande de justification existe déjà pour cet événement.', 422);
        }

        $validated = $request->validate([
            'motif' => ['required', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:3000'],
            'piece_jointe' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $pieceJointe = null;

        if ($request->hasFile('piece_jointe')) {
            $pieceJointe = $request->file('piece_jointe')->store('parent/justifications', 'public');
        }

        $justification = JustificationAbsenceRetard::create([
            'absence_retard_id' => $absenceRetard->id,
            'parent_id' => $request->user()->id,
            'motif' => $validated['motif'],
            'message' => $validated['message'] ?? null,
            'piece_jointe' => $pieceJointe,
            'statut' => JustificationAbsenceRetard::STATUT_EN_ATTENTE,
        ]);

        $justification->load(['parent', 'traitePar']);
        $absenceRetard->setRelation('justificationParentale', $justification);

        return $this->success('Justification envoyée. Elle attend la validation de l’école.', [
            'absence_retard' => $this->formatAbsenceRetard($absenceRetard),
            'justification' => $this->formatJustification($justification),
        ], 201);
    }

    public function pieceJustification(Request $request, JustificationAbsenceRetard $justification): Response|JsonResponse
    {
        $justification->loadMissing('absenceRetard.inscription');

        if (! $justification->piece_jointe
            || ! $justification->absenceRetard
            || ! $this->parentAccessService->canAccessAbsenceRetard($request->user(), $justification->absenceRetard)) {
            return $this->error('Pièce jointe introuvable.', 404);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->response($justification->piece_jointe);
    }

    public function sanctions(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annee_scolaires,id'],
            'trimestre_id' => ['nullable', 'integer', 'exists:trimestres,id'],
            'statut' => ['nullable', Rule::in(SanctionAppliquee::STATUTS)],
        ]);

        $contexte = $this->contexteInscriptions($eleve, $validated['annee_scolaire_id'] ?? null, false);
        $inscriptionIds = $contexte['inscriptions']->pluck('id');

        $sanctions = SanctionAppliquee::with([
                'sanction',
                'trimestre',
                'inscription.classe.anneeScolaire',
                'appliquePar',
                'decisionPar',
            ])
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('visible_parent', true)
            ->when($validated['trimestre_id'] ?? null, fn ($query, $trimestreId) => $query->where('trimestre_id', $trimestreId))
            ->when($validated['statut'] ?? null, fn ($query, $statut) => $query->where('statut', $statut))
            ->orderByDesc('date_application')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SanctionAppliquee $sanctionAppliquee) => $this->formatSanctionAppliquee($sanctionAppliquee));

        return $this->success('Sanctions récupérées avec succès.', [
            'eleve' => $this->formatEleveSimple($eleve),
            'annee_selectionnee' => $this->formatAnnee($contexte['annee']),
            'total' => $sanctions->count(),
            'sanctions' => $sanctions,
        ]);
    }

    public function reinscription(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annee_scolaires,id'],
        ]);

        $contexte = $this->contexteInscriptions($eleve, $validated['annee_scolaire_id'] ?? null, false);
        $inscription = $contexte['inscription_reference'];

        $option = $inscription
            ? $this->reinscriptionService->optionPourInscription($inscription)
            : $this->reinscriptionService->optionPremiereInscription($eleve, $contexte['annee']);

        $demandes = DemandeReinscription::with([
                'ancienneInscription.classe.anneeScolaire',
                'ancienneClasse',
                'nouvelleAnneeScolaire',
                'classeDemandee',
                'inscriptionCreee',
                'validePar',
            ])
            ->where('eleve_id', $eleve->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DemandeReinscription $demande) => $this->formatDemandeReinscription($demande));

        return $this->success('Informations de réinscription récupérées avec succès.', [
            'eleve' => $this->formatEleveSimple($eleve),
            'annee_selectionnee' => $this->formatAnnee($contexte['annee']),
            'inscription_reference' => $this->formatInscriptionSimple($inscription),
            'option' => $this->formatOptionReinscription($option),
            'demandes' => $demandes,
        ]);
    }

    public function demanderReinscription(Request $request, Eleve $eleve): JsonResponse
    {
        $this->assertParentCanAccessEleve($request, $eleve);

        $validated = $request->validate([
            'ancienne_inscription_id' => ['nullable', 'integer', 'exists:inscriptions,id'],
            'classe_demandee_id' => ['required', 'integer', 'exists:classes,id'],
            'commentaire_parent' => ['nullable', 'string', 'max:2000'],
        ]);

        $classeDemandee = Classe::with('anneeScolaire')->findOrFail($validated['classe_demandee_id']);
        $inscription = null;
        $estPremiereInscription = ! Inscription::query()->where('eleve_id', $eleve->id)->exists();

        try {
            if ($estPremiereInscription) {
                $option = $this->reinscriptionService->verifierClassePremiereInscription($eleve, $classeDemandee);
            } else {
                $inscription = $this->inscriptionAction($eleve, $validated['ancienne_inscription_id'] ?? null);
                $option = $this->reinscriptionService->verifierClasseAutorisee($inscription, $classeDemandee);
            }
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $demande = DemandeReinscription::create([
            'eleve_id' => $eleve->id,
            'parent_id' => $request->user()->id,
            'ancienne_inscription_id' => $inscription?->id,
            'ancienne_classe_id' => $inscription?->classe_id,
            'nouvelle_annee_scolaire_id' => $option['nouvelle_annee']->id,
            'classe_demandee_id' => $classeDemandee->id,
            'type_demande' => $option['type_demande'],
            'decision_systeme' => $option['decision_systeme'],
            'statut' => DemandeReinscription::STATUT_EN_ATTENTE,
            'commentaire_parent' => $validated['commentaire_parent'] ?? null,
        ]);

        $demande->load([
            'ancienneInscription.classe.anneeScolaire',
            'ancienneClasse',
            'nouvelleAnneeScolaire',
            'classeDemandee',
            'inscriptionCreee',
            'validePar',
        ]);

        $message = ($demande->type_demande === DemandeReinscription::TYPE_PREMIERE_INSCRIPTION)
            ? 'Demande de première inscription envoyée. Elle attend la validation du gestionnaire.'
            : 'Demande de réinscription envoyée. Elle attend la validation du gestionnaire.';

        return $this->success($message, [
            'demande' => $this->formatDemandeReinscription($demande),
        ], 201);
    }

    private function assertParentCanAccessEleve(Request $request, Eleve $eleve): void
    {
        $this->parentAccessService->assertCanAccessEleve($request->user(), $eleve);
    }

    private function contexteInscriptions(Eleve $eleve, ?int $anneeScolaireId, bool $preferActive = true): array
    {
        $annee = $anneeScolaireId
            ? AnneeScolaire::find($anneeScolaireId)
            : ($this->anneeScolaireCourante() ?? AnneeScolaire::orderByDesc('date_debut')->first());

        $inscriptions = Inscription::with(['classe.anneeScolaire', 'anneeScolaire', 'paiements', 'eleve'])
            ->where('eleve_id', $eleve->id)
            ->when($annee, fn ($query) => $query->where('annee_scolaire_id', $annee->id))
            ->orderByDesc('date_inscription')
            ->get();

        if ($inscriptions->isEmpty() && ! $anneeScolaireId && ! $preferActive) {
            $inscriptions = Inscription::with(['classe.anneeScolaire', 'anneeScolaire', 'paiements', 'eleve'])
                ->where('eleve_id', $eleve->id)
                ->orderByDesc('date_inscription')
                ->get();

            $annee = $inscriptions->first()?->anneeScolaire;
        }

        return [
            'annee' => $annee,
            'inscriptions' => $inscriptions,
            'inscription_reference' => $inscriptions->first(),
        ];
    }

    private function inscriptionAction(Eleve $eleve, ?int $inscriptionId): Inscription
    {
        if ($inscriptionId) {
            return Inscription::with(['eleve', 'classe.anneeScolaire', 'anneeScolaire', 'paiements'])
                ->where('eleve_id', $eleve->id)
                ->findOrFail($inscriptionId);
        }

        $contexte = $this->contexteInscriptions($eleve, null, false);

        if (! $contexte['inscription_reference']) {
            abort(404, 'Aucune inscription disponible pour cet enfant.');
        }

        return $contexte['inscription_reference'];
    }

    private function inscriptionPourTrimestre(Eleve $eleve, Trimestre $trimestre): ?Inscription
    {
        return Inscription::with(['eleve', 'classe.anneeScolaire', 'anneeScolaire', 'paiements'])
            ->where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $trimestre->annee_scolaire_id)
            ->latest('date_inscription')
            ->first();
    }

    private function situationFinanciere(Collection $inscriptions): array
    {
        $fraisAttendu = (float) $inscriptions->sum(fn (Inscription $inscription) => (float) $inscription->frais_attendu);
        $totalPaye = (float) Paiement::query()
            ->whereIn('inscription_id', $inscriptions->pluck('id'))
            ->sum('montant');
        $reste = max(0, $fraisAttendu - $totalPaye);

        return [
            'frais_attendu' => $fraisAttendu,
            'total_paye' => $totalPaye,
            'reste_a_payer' => $reste,
            'est_solde' => $reste <= 0,
            'taux_paiement' => $fraisAttendu > 0 ? round(($totalPaye / $fraisAttendu) * 100, 2) : 0,
        ];
    }

    private function formatResultatTrimestriel(?Inscription $inscription, Trimestre $trimestre): array
    {
        if (! $inscription) {
            return [
                'trimestre' => $this->formatTrimestre($trimestre),
                'disponible' => false,
                'message' => 'Aucune inscription disponible pour ce trimestre.',
                'data' => null,
                'download_url' => null,
            ];
        }

        try {
            $data = $this->bulletinService->bulletinTrimestriel($inscription, $trimestre);

            return [
                'trimestre' => $this->formatTrimestre($trimestre),
                'disponible' => true,
                'message' => null,
                'data' => $this->formatBulletinTrimestrielData($data),
                'download_url' => route('api.parent.enfants.bulletins.trimestriel.telecharger', [$inscription->eleve_id, $trimestre], true),
            ];
        } catch (RuntimeException $exception) {
            return [
                'trimestre' => $this->formatTrimestre($trimestre),
                'disponible' => false,
                'message' => $exception->getMessage(),
                'data' => null,
                'download_url' => null,
            ];
        }
    }

    private function formatBulletinAnnuelDisponible(?Inscription $inscription): array
    {
        if (! $inscription) {
            return [
                'disponible' => false,
                'message' => 'Aucune inscription sélectionnée.',
                'data' => null,
                'download_url' => null,
            ];
        }

        try {
            $data = $this->bulletinService->bulletinAnnuel($inscription);

            return [
                'disponible' => true,
                'message' => null,
                'data' => [
                    'moyenne_annuelle' => $data['moyenne_annuelle'] ?? null,
                    'rang_annuel' => $data['rang_annuel'] ?? null,
                    'effectif' => $data['effectif'] ?? null,
                    'appreciation' => $data['appreciation'] ?? null,
                    'decision' => $data['decision'] ?? null,
                ],
                'download_url' => route('api.parent.enfants.bulletins.annuel.telecharger', [
                    'eleve' => $inscription->eleve_id,
                    'annee_scolaire_id' => $inscription->annee_scolaire_id,
                ], true),
            ];
        } catch (RuntimeException $exception) {
            return [
                'disponible' => false,
                'message' => $exception->getMessage(),
                'data' => null,
                'download_url' => null,
            ];
        }
    }

    private function formatBulletinTrimestrielData(array $data): array
    {
        return [
            'moyenne' => $data['moyenne'] ?? null,
            'moyenne_finale' => $data['moyenne_finale'] ?? null,
            'moyenne_avant_sanction' => $data['moyenne_avant_sanction'] ?? null,
            'rang' => $data['rang'] ?? null,
            'effectif' => $data['effectif'] ?? null,
            'appreciation' => $data['appreciation'] ?? null,
            'total_coefficients' => $data['total_coefficients'] ?? null,
            'total_pondere' => $data['total_pondere'] ?? null,
            'total_pondere_final' => $data['total_pondere_final'] ?? null,
            'total_points_en_moins' => $data['total_points_en_moins'] ?? 0,
            'total_points_en_moins_visibles' => $data['total_points_en_moins_visibles'] ?? 0,
            'lignes' => collect($data['lignes'] ?? [])->map(function ($ligne) {
                return [
                    'matiere_id' => $ligne['matiere_id'] ?? null,
                    'matiere' => $ligne['matiere'] ?? null,
                    'type' => $ligne['type'] ?? null,
                    'note' => $ligne['note'] ?? null,
                    'bareme' => $ligne['bareme'] ?? null,
                    'note_sur_20' => isset($ligne['note_sur_20']) ? round((float) $ligne['note_sur_20'], 2) : null,
                    'coefficient' => $ligne['coefficient'] ?? null,
                    'points' => $ligne['points'] ?? null,
                    'appreciation' => $ligne['appreciation'] ?? null,
                ];
            })->values(),
        ];
    }

    private function formatNote(Note $note): array
    {
        $evaluation = $note->evaluation;

        return [
            'id' => $note->id,
            'valeur' => (float) $note->valeur,
            'appreciation' => $note->appreciation,
            'note_sur_20' => $evaluation && (float) $evaluation->bareme > 0
                ? round(((float) $note->valeur / (float) $evaluation->bareme) * 20, 2)
                : null,
            'evaluation' => $evaluation ? [
                'id' => $evaluation->id,
                'nom' => $evaluation->nom,
                'type' => $evaluation->type,
                'date_evaluation' => $this->dateValue($evaluation->date_evaluation),
                'heure_debut' => $this->timeValue($evaluation->heure_debut),
                'heure_fin' => $this->timeValue($evaluation->heure_fin),
                'bareme' => (float) $evaluation->bareme,
                'coefficient' => (float) $evaluation->coefficient,
                'matiere' => $evaluation->matiere ? [
                    'id' => $evaluation->matiere->id,
                    'nom' => $evaluation->matiere->nom,
                ] : null,
                'trimestre' => $this->formatTrimestre($evaluation->trimestre),
            ] : null,
            'inscription' => $this->formatInscriptionSimple($note->inscription),
        ];
    }

    private function formatPaiement(Paiement $paiement): array
    {
        return [
            'id' => $paiement->id,
            'numero_paiement' => $paiement->numero_paiement,
            'montant' => (float) $paiement->montant,
            'date_paiement' => $this->dateValue($paiement->date_paiement),
            'mode_paiement' => $paiement->mode_paiement,
            'contact_parent' => $paiement->contact_parent,
            'contact_gestionnaire' => $paiement->contact_gestionnaire,
            'gestionnaire' => $this->formatUserSimple($paiement->gestionnaire),
            'inscription' => $this->formatInscriptionSimple($paiement->inscription),
            'recu_url' => route('api.parent.paiements.recu', $paiement, true),
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
            'libelle_statut' => $paiementDeclare->libelleStatut(),
            'commentaire_validation' => $paiementDeclare->commentaire_validation,
            'valide_le' => $this->dateTimeValue($paiementDeclare->valide_le),
            'valide_par' => $this->formatUserSimple($paiementDeclare->validePar),
            'paiement' => $paiementDeclare->paiement ? $this->formatPaiement($paiementDeclare->paiement) : null,
            'preuve_paiement' => $paiementDeclare->preuve_paiement,
            'preuve_url' => $this->storageUrl($paiementDeclare->preuve_paiement),
            'preuve_api_url' => $paiementDeclare->preuve_paiement
                ? route('api.parent.paiements-declares.preuve', $paiementDeclare, true)
                : null,
            'inscription' => $this->formatInscriptionSimple($paiementDeclare->inscription),
            'cree_le' => $this->dateTimeValue($paiementDeclare->created_at),
        ];
    }

    private function formatAbsenceRetard(AbsenceRetard $absenceRetard): array
    {
        $justification = $absenceRetard->justificationParentale;

        return [
            'id' => $absenceRetard->id,
            'type' => $absenceRetard->type,
            'libelle_type' => $absenceRetard->libelleType(),
            'date_debut' => $this->dateValue($absenceRetard->date_debut),
            'date_fin' => $this->dateValue($absenceRetard->date_fin),
            'periode' => $absenceRetard->periode,
            'libelle_periode' => $absenceRetard->libellePeriode(),
            'heure_debut' => $this->timeValue($absenceRetard->heure_debut),
            'heure_fin' => $this->timeValue($absenceRetard->heure_fin),
            'heure_arrivee' => $this->timeValue($absenceRetard->heure_arrivee),
            'duree_minutes' => $absenceRetard->duree_minutes,
            'categorie_motif' => $absenceRetard->categorie_motif,
            'motif' => $absenceRetard->motif,
            'statut' => $absenceRetard->statut,
            'libelle_statut' => $absenceRetard->libelleStatut(),
            'justification_officielle' => $absenceRetard->justification,
            'piece_justificative' => $absenceRetard->piece_justificative,
            'piece_justificative_url' => $this->storageUrl($absenceRetard->piece_justificative),
            'source_signalement' => $absenceRetard->source_signalement,
            'enregistre_par' => $this->formatUserSimple($absenceRetard->enregistrePar),
            'inscription' => $this->formatInscriptionSimple($absenceRetard->inscription),
            'justification_parentale' => $justification ? $this->formatJustification($justification) : null,
            'peut_etre_justifie' => $absenceRetard->statut !== 'justifiee' && ! $justification,
            'justifier_url' => route('api.parent.absences-retards.justifier', $absenceRetard, true),
        ];
    }

    private function formatJustification(JustificationAbsenceRetard $justification): array
    {
        return [
            'id' => $justification->id,
            'motif' => $justification->motif,
            'message' => $justification->message,
            'statut' => $justification->statut,
            'libelle_statut' => $justification->libelleStatut(),
            'piece_jointe' => $justification->piece_jointe,
            'piece_jointe_url' => $this->storageUrl($justification->piece_jointe),
            'piece_api_url' => $justification->piece_jointe
                ? route('api.parent.justifications.piece', $justification, true)
                : null,
            'traite_le' => $this->dateTimeValue($justification->traite_le),
            'traite_par' => $this->formatUserSimple($justification->traitePar),
            'commentaire_traitement' => $justification->commentaire_traitement,
            'cree_le' => $this->dateTimeValue($justification->created_at),
        ];
    }

    private function formatSanctionAppliquee(SanctionAppliquee $sanctionAppliquee): array
    {
        return [
            'id' => $sanctionAppliquee->id,
            'origine' => $sanctionAppliquee->origine,
            'date_application' => $this->dateValue($sanctionAppliquee->date_application),
            'periode_debut' => $this->dateValue($sanctionAppliquee->periode_debut),
            'periode_fin' => $this->dateValue($sanctionAppliquee->periode_fin),
            'nombre_evenements' => $sanctionAppliquee->nombre_evenements,
            'motif' => $sanctionAppliquee->motif,
            'statut' => $sanctionAppliquee->statut,
            'libelle_statut' => match ($sanctionAppliquee->statut) {
                'appliquee' => 'En cours',
                'terminee' => 'Définitive',
                'annulee' => 'Annulée',
                'ignoree' => 'Ignorée',
                default => 'Proposée',
            },
            'type_effet' => $sanctionAppliquee->type_effet,
            'valeur_effet' => $sanctionAppliquee->valeur_effet !== null ? (float) $sanctionAppliquee->valeur_effet : null,
            'trimestre' => $this->formatTrimestre($sanctionAppliquee->trimestre),
            'sanction' => $sanctionAppliquee->sanction ? [
                'id' => $sanctionAppliquee->sanction->id,
                'nom' => $sanctionAppliquee->sanction->nom,
                'type' => $sanctionAppliquee->sanction->type,
                'mesure' => $sanctionAppliquee->sanction->mesure,
                'description' => $sanctionAppliquee->sanction->description,
            ] : null,
            'applique_par' => $this->formatUserSimple($sanctionAppliquee->appliquePar),
            'decision_par' => $this->formatUserSimple($sanctionAppliquee->decisionPar),
            'decision_le' => $this->dateTimeValue($sanctionAppliquee->decision_le),
            'inscription' => $this->formatInscriptionSimple($sanctionAppliquee->inscription),
        ];
    }

    private function formatDemandeReinscription(DemandeReinscription $demande): array
    {
        return [
            'id' => $demande->id,
            'type_demande' => $demande->type_demande,
            'libelle_type_demande' => $demande->libelleTypeDemande(),
            'decision_systeme' => $demande->decision_systeme,
            'libelle_decision_systeme' => $demande->libelleDecisionSysteme(),
            'statut' => $demande->statut,
            'libelle_statut' => $demande->libelleStatut(),
            'ancienne_inscription' => $this->formatInscriptionSimple($demande->ancienneInscription),
            'ancienne_classe' => $this->formatClasse($demande->ancienneClasse),
            'nouvelle_annee_scolaire' => $this->formatAnnee($demande->nouvelleAnneeScolaire),
            'classe_demandee' => $this->formatClasse($demande->classeDemandee),
            'inscription_creee' => $this->formatInscriptionSimple($demande->inscriptionCreee),
            'commentaire_parent' => $demande->commentaire_parent,
            'commentaire_gestionnaire' => $demande->commentaire_gestionnaire,
            'valide_par' => $this->formatUserSimple($demande->validePar),
            'valide_le' => $this->dateTimeValue($demande->valide_le),
            'cree_le' => $this->dateTimeValue($demande->created_at),
        ];
    }

    private function formatOptionReinscription(array $option): array
    {
        return [
            'possible' => (bool) ($option['possible'] ?? false),
            'raison' => $option['raison'] ?? null,
            'nouvelle_annee' => $this->formatAnnee($option['nouvelle_annee'] ?? null),
            'niveau_demande' => $option['niveau_demande'] ?? null,
            'type_demande' => $option['type_demande'] ?? null,
            'decision_systeme' => $option['decision_systeme'] ?? null,
            'classes_disponibles' => collect($option['classes_disponibles'] ?? [])
                ->map(fn (Classe $classe) => $this->formatClasse($classe))
                ->values(),
            'demande_en_attente' => isset($option['demande_en_attente']) && $option['demande_en_attente']
                ? $this->formatDemandeReinscription($option['demande_en_attente'])
                : null,
        ];
    }

    private function formatEleveSimple(Eleve $eleve): array
    {
        return [
            'id' => $eleve->id,
            'matricule' => $eleve->matricule,
            'nom' => $eleve->nom,
            'prenom' => $eleve->prenom,
            'nom_complet' => $eleve->nomComplet(),
            'sexe' => $eleve->sexe,
            'date_naissance' => $this->dateValue($eleve->date_naissance),
            'lieu_naissance' => $eleve->lieu_naissance,
            'photo' => $eleve->photo,
            'photo_url' => $this->storageUrl($eleve->photo),
        ];
    }

    private function formatInscriptionSimple(?Inscription $inscription): ?array
    {
        if (! $inscription) {
            return null;
        }

        return [
            'id' => $inscription->id,
            'date_inscription' => $this->dateValue($inscription->date_inscription),
            'statut' => $inscription->statut,
            'frais_attendu' => (float) $inscription->frais_attendu,
            'classe' => $this->formatClasse($inscription->classe),
            'annee_scolaire' => $this->formatAnnee($inscription->anneeScolaire),
        ];
    }

    private function formatClasse(?Classe $classe): ?array
    {
        if (! $classe) {
            return null;
        }

        return [
            'id' => $classe->id,
            'niveau' => $classe->niveau,
            'nom' => $classe->nom,
            'frais_scolarite' => (float) $classe->frais_scolarite,
            'annee_scolaire' => $this->formatAnnee($classe->anneeScolaire),
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
            'date_debut' => $this->dateValue($annee->date_debut),
            'date_fin' => $this->dateValue($annee->date_fin),
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
            'date_debut' => $this->dateValue($trimestre->date_debut),
            'date_fin' => $this->dateValue($trimestre->date_fin),
            'statut' => $trimestre->statut,
            'statut_pedagogique' => $trimestre->statutPedagogique(),
        ];
    }

    private function formatMatiere(?Matiere $matiere): ?array
    {
        if (! $matiere) {
            return null;
        }

        return [
            'id' => $matiere->id,
            'nom' => $matiere->nom,
        ];
    }

    private function formatOptions(array $values, array $labels = []): array
    {
        return collect($values)
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => $labels[$value] ?? ucfirst(str_replace('_', ' ', $value)),
            ])
            ->values()
            ->all();
    }

    private function formatUserSimple($user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'name' => $user->name,
            'role' => $user->role,
        ];
    }

    private function anneeScolaireCourante(): ?AnneeScolaire
    {
        return AnneeScolaire::where('statut', 'active')
            ->orderByDesc('date_debut')
            ->first();
    }

    private function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url('/storage/' . ltrim($path, '/'));
    }

    private function dateValue($value): ?string
    {
        if (! $value) {
            return null;
        }

        return method_exists($value, 'toDateString') ? $value->toDateString() : (string) $value;
    }

    private function dateTimeValue($value): ?string
    {
        if (! $value) {
            return null;
        }

        return method_exists($value, 'toDateTimeString') ? $value->toDateTimeString() : (string) $value;
    }

    private function timeValue( $value): ?string
    {
        if (! $value) {
            return null;
        }

        return method_exists($value, 'format') ? $value->format('H:i') : (string) $value;
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: 'document';

        return trim($value, '-');
    }

    private function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
