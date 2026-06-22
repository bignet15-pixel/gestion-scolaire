<?php

namespace App\Http\Controllers;

use App\Models\AbsenceRetard;
use App\Models\AnneeScolaire;
use App\Models\DemandeReinscription;
use App\Models\Eleve;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\PaiementDeclare;
use App\Models\SanctionAppliquee;
use App\Models\Trimestre;
use App\Services\BulletinService;
use App\Services\ParentAccessService;
use App\Services\ParentReinscriptionService;
use Illuminate\Http\Request;
use RuntimeException;

class ParentEleveController extends Controller
{
    public function __construct(
        private ParentAccessService $parentAccessService,
        private ParentReinscriptionService $reinscriptionService,
        private BulletinService $bulletinService
    ) {}

    /**
     * Affiche la fiche complète d'un enfant pour le parent connecté.
     */
    public function show(Request $request, Eleve $eleve)
    {
        $parent = auth()->user();
        $this->parentAccessService->assertCanAccessEleve($parent, $eleve);

        $eleve->load([
            'inscriptions.classe.anneeScolaire',
            'inscriptions.anneeScolaire',
            'inscriptions.paiements',
        ]);

        // Le filtre doit proposer toutes les années scolaires créées dans l'école.
        // Par défaut, on affiche l'année active, mais le parent peut revenir
        // sur une année passée pour consulter l'historique de l'enfant.
        $annees = AnneeScolaire::query()
            ->orderByDesc('date_debut')
            ->get();

        $anneeActive = $this->anneeScolaireCourante();
        $selectedAnneeId = $request->input('annee_scolaire_id')
            ?: ($anneeActive?->id ?? $annees->first()?->id);
        $selectedTrimestreId = $request->input('trimestre_id');
        $selectedType = $request->input('type');
        $selectedStatut = $request->input('statut');

        $trimestres = Trimestre::query()
            ->when($selectedAnneeId, fn ($query) => $query->where('annee_scolaire_id', $selectedAnneeId))
            ->orderBy('date_debut')
            ->get();

        $inscriptionsFiltrees = $eleve->inscriptions
            ->when($selectedAnneeId, fn ($collection) => $collection->where('annee_scolaire_id', (int) $selectedAnneeId))
            ->values();

        $inscriptionIds = $inscriptionsFiltrees->pluck('id');
        $inscriptionPrincipale = $inscriptionsFiltrees->sortByDesc('date_inscription')->first();

        $notes = Note::with([
                'evaluation.matiere',
                'evaluation.trimestre',
                'inscription.classe.anneeScolaire',
            ])
            ->whereIn('inscription_id', $inscriptionIds)
            ->when($selectedTrimestreId, function ($query) use ($selectedTrimestreId) {
                $query->whereHas('evaluation', function ($q) use ($selectedTrimestreId) {
                    $q->where('trimestre_id', $selectedTrimestreId);
                });
            })
            ->whereNotNull('valeur')
            ->get()
            ->sortByDesc(fn ($note) => $note->evaluation?->date_evaluation)
            ->values();

        $resultatsTrimestriels = $this->resultatsTrimestriels($inscriptionPrincipale, $trimestres);
        $bulletinAnnuel = $this->bulletinAnnuel($inscriptionPrincipale);

        $paiements = Paiement::with(['gestionnaire', 'inscription.classe.anneeScolaire'])
            ->whereIn('inscription_id', $inscriptionIds)
            ->orderByDesc('date_paiement')
            ->get();

        $paiementsDeclares = PaiementDeclare::with(['paiement', 'validePar'])
            ->whereIn('inscription_id', $inscriptionIds)
            ->orderByDesc('created_at')
            ->get();

        $absencesRetards = AbsenceRetard::with(['inscription.classe', 'justificationParentale'])
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('visible_parent', true)
            ->when($selectedType, fn ($query) => $query->where('type', $selectedType))
            ->when($selectedStatut, fn ($query) => $query->where('statut', $selectedStatut))
            ->when($selectedTrimestreId, function ($query) use ($selectedTrimestreId) {
                $query->whereExists(function ($subQuery) use ($selectedTrimestreId) {
                    $subQuery->selectRaw('1')
                        ->from('trimestres')
                        ->where('trimestres.id', $selectedTrimestreId)
                        ->whereColumn('absences_retards.date_debut', '>=', 'trimestres.date_debut')
                        ->whereColumn('absences_retards.date_debut', '<=', 'trimestres.date_fin');
                });
            })
            ->orderByDesc('date_debut')
            ->get();

        $sanctions = SanctionAppliquee::with(['sanction', 'trimestre', 'inscription.classe'])
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('visible_parent', true)
            ->when($selectedTrimestreId, fn ($query) => $query->where('trimestre_id', $selectedTrimestreId))
            ->when($selectedStatut, fn ($query) => $query->where('statut', $selectedStatut))
            ->orderByDesc('created_at')
            ->get();

        $reinscriptionOption = $inscriptionPrincipale
            ? $this->reinscriptionService->optionPourInscription($inscriptionPrincipale)
            : [
                'possible' => false,
                'raison' => 'Aucune inscription sélectionnée.',
                'classes_disponibles' => collect(),
                'demande_en_attente' => null,
            ];

        $demandesReinscription = DemandeReinscription::with([
                'classeDemandee',
                'nouvelleAnneeScolaire',
                'validePar',
                'inscriptionCreee',
            ])
            ->where('eleve_id', $eleve->id)
            ->orderByDesc('created_at')
            ->get();

        return view('parent.eleves.show', compact(
            'eleve',
            'annees',
            'trimestres',
            'selectedAnneeId',
            'selectedTrimestreId',
            'selectedType',
            'selectedStatut',
            'inscriptionsFiltrees',
            'inscriptionPrincipale',
            'notes',
            'resultatsTrimestriels',
            'bulletinAnnuel',
            'paiements',
            'paiementsDeclares',
            'absencesRetards',
            'sanctions',
            'reinscriptionOption',
            'demandesReinscription'
        ));
    }

    private function resultatsTrimestriels($inscription, $trimestres)
    {
        if (! $inscription) {
            return collect();
        }

        return $trimestres->map(function (Trimestre $trimestre) use ($inscription) {
            try {
                return [
                    'trimestre' => $trimestre,
                    'disponible' => true,
                    'message' => null,
                    'data' => $this->bulletinService->bulletinTrimestriel($inscription, $trimestre),
                ];
            } catch (RuntimeException $exception) {
                return [
                    'trimestre' => $trimestre,
                    'disponible' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                ];
            }
        });
    }

    private function bulletinAnnuel($inscription): array
    {
        if (! $inscription) {
            return [
                'disponible' => false,
                'message' => 'Aucune inscription sélectionnée.',
                'data' => null,
            ];
        }

        try {
            return [
                'disponible' => true,
                'message' => null,
                'data' => $this->bulletinService->bulletinAnnuel($inscription),
            ];
        } catch (RuntimeException $exception) {
            return [
                'disponible' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ];
        }
    }

    private function anneeScolaireCourante(): ?AnneeScolaire
    {
        return AnneeScolaire::where('statut', 'active')
            ->orderByDesc('date_debut')
            ->first()
            ?? AnneeScolaire::orderByDesc('date_debut')->first();
    }
}
