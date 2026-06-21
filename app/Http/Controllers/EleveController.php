<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Trimestre;
use App\Services\BulletinService;
use App\Services\ResultatTrimestrielService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EleveController extends Controller
{
    public function __construct(
        private ResultatTrimestrielService $resultatTrimestrielService
    ) {}

    /**
     * Affiche la liste des élèves avec filtre par année d'inscription.
     */
    public function index(Request $request)
    {
        $selectedAnneeId = $request->input('annee_scolaire_id');

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $eleves = Eleve::with([
            'inscriptions.classe.anneeScolaire',
        ])
            ->withCount('inscriptions')
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->whereHas('inscriptions', function ($q) use ($selectedAnneeId) {
                    $q->where('annee_scolaire_id', $selectedAnneeId);
                });
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('eleves.index', compact(
            'eleves',
            'annees',
            'selectedAnneeId'
        ));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        return view('eleves.create');
    }

    /**
     * Enregistre un nouvel élève.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['required', 'in:M,F'],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'contact_parent' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $validated['matricule'] = $this->genererMatriculeEleve();

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('eleves/photos', 'public');
        }

        Eleve::create($validated);

        return redirect()
            ->route('eleves.index')
            ->with('success', 'Élève créé avec succès.');
    }

    /**
     * Affiche la fiche détaillée d'un élève.
     */
    public function show(Request $request, Eleve $eleve)
    {
        $selectedAnneeId = $request->filled('annee_scolaire_id')
            ? $request->input('annee_scolaire_id')
            : null;
        $selectedClasseId = $request->filled('classe_id')
            ? $request->input('classe_id')
            : null;
        $filtreSoumis = $request->has('annee_scolaire_id') || $request->has('classe_id');

        $inscriptionsOptions = $eleve->inscriptions()
            ->with('classe.anneeScolaire')
            ->orderByDesc('date_inscription')
            ->get();

        if (! $filtreSoumis && $inscriptionsOptions->isNotEmpty()) {
            $derniereInscription = $inscriptionsOptions->first();
            $selectedAnneeId = $derniereInscription->annee_scolaire_id;
            $selectedClasseId = $derniereInscription->classe_id;
        }

        if ($selectedClasseId) {
            $classeValide = $inscriptionsOptions->first(function ($inscription) use ($selectedAnneeId, $selectedClasseId) {
                if ((string) $inscription->classe_id !== (string) $selectedClasseId) {
                    return false;
                }

                return ! $selectedAnneeId
                    || (string) $inscription->annee_scolaire_id === (string) $selectedAnneeId;
            });

            if (! $classeValide) {
                $selectedClasseId = null;
            }
        }

        $inscriptionsFiltrees = $eleve->inscriptions()
            ->with([
                'classe.anneeScolaire',
                'paiements',
                'notes.evaluation.matiere',
                'notes.evaluation.trimestre',
            ])
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->when($selectedClasseId, function ($query) use ($selectedClasseId) {
                $query->where('classe_id', $selectedClasseId);
            })
            ->orderByDesc('date_inscription')
            ->get();

        $eleve->setRelation('inscriptions', $inscriptionsFiltrees);

        $resultatsParInscription = $this->construireResultatsEleve($inscriptionsFiltrees);

        return view('eleves.show', compact(
            'eleve',
            'resultatsParInscription',
            'inscriptionsOptions',
            'selectedAnneeId',
            'selectedClasseId'
        ));
    }

    /**
     * Construit les résultats trimestriels de l'élève pour chaque inscription.
     */
    private function construireResultatsEleve($inscriptions)
    {
        $resultats = collect();
        $bulletinService = app(BulletinService::class);

        foreach ($inscriptions as $inscription) {
            $trimestres = Trimestre::where('annee_scolaire_id', $inscription->annee_scolaire_id)
                ->orderBy('date_debut')
                ->get();

            $totalCoefficientsClasse = $this->totalCoefficientsClasse((int) $inscription->classe_id);

            $resultatsTrimestres = collect();

            foreach ($trimestres as $trimestre) {
                $statutPedagogique = $trimestre->statutPedagogique();
                $evaluationsAttendues = $bulletinService->nombreEvaluationsAttendues($inscription, $trimestre);
                $notesManquantes = $bulletinService->nombreNotesManquantes($inscription, $trimestre);
                $publie = $trimestre->estFerme()
                    && $evaluationsAttendues > 0
                    && $notesManquantes === 0;
                $notes = collect();
                $moyenne = null;
                $rang = null;
                $appreciation = 'Résultats non publiés';
                $totalPondere = null;
                $totalPointsEnMoins = 0;
                $totalPondereFinal = null;
                $moyenneAvantSanction = null;

                if ($publie) {
                    $notes = $inscription->notes
                        ->filter(function ($note) use ($trimestre) {
                            return $note->evaluation
                                && (int) $note->evaluation->trimestre_id === (int) $trimestre->id;
                        })
                        ->values();

                    $totalPondere = $this->calculerTotalPondereNotes($notes);
                    $details = $this->resultatTrimestrielService->appliquerRetenues(
                        $inscription->id,
                        $trimestre->id,
                        $totalPondere,
                        $totalCoefficientsClasse
                    );
                    $moyenne = $details['moyenne_finale'];
                    $moyenneAvantSanction = $details['moyenne_avant_sanction'];
                    $totalPondere = $details['total_pondere'];
                    $totalPointsEnMoins = $details['total_points_en_moins'];
                    $totalPondereFinal = $details['total_pondere_final'];

                    $rang = $this->calculerRangEleveDansClasse(
                        $inscription,
                        $trimestre,
                        $moyenne,
                        $totalCoefficientsClasse
                    );

                    $appreciation = $moyenne !== null
                        ? $this->appreciationMoyenne($moyenne)
                        : '-';
                }

                $resultatsTrimestres->push([
                    'trimestre' => $trimestre,
                    'publie' => $publie,
                    'statut_pedagogique' => $statutPedagogique,
                    'statut_libelle' => $trimestre->libelleStatutPedagogique(),
                    'statut_badge' => $trimestre->badgeStatutPedagogique(),
                    'evaluations_attendues' => $evaluationsAttendues,
                    'notes_manquantes' => $notesManquantes,
                    'notes' => $notes,
                    'moyenne' => $moyenne,
                    'moyenne_finale' => $moyenne,
                    'rang' => $rang,
                    'appreciation' => $appreciation,
                    'total_pondere' => $totalPondere,
                    'total_points_en_moins' => $totalPointsEnMoins,
                    'total_points_en_moins_visibles' => $details['total_points_en_moins_visibles'] ?? $totalPointsEnMoins,
                    'total_points_en_moins_en_cours' => $details['total_points_en_moins_en_cours'] ?? 0,
                    'total_points_en_moins_definitifs' => $details['total_points_en_moins_definitifs'] ?? $totalPointsEnMoins,
                    'total_pondere_final' => $totalPondereFinal,
                    'moyenne_avant_sanction' => $moyenneAvantSanction,
                    'total_coefficients' => $totalCoefficientsClasse,
                ]);
            }

            $resultats->push([
                'inscription' => $inscription,
                'trimestres' => $resultatsTrimestres,
                'annuel' => $this->calculerResultatAnnuelInscription(
                    $inscription,
                    $trimestres,
                    $resultatsTrimestres,
                    $totalCoefficientsClasse
                ),
            ]);
        }

        return $resultats;
    }

    private function calculerTotalPondereNotes($notes): float
    {
        return $this->resultatTrimestrielService->calculerTotalPondereParMatiere($notes);
    }

    private function calculerResultatAnnuelInscription(
        Inscription $inscription,
        $trimestres,
        $resultatsTrimestres,
        float $totalCoefficientsClasse
    ): array {
        if ($trimestres->count() !== 3) {
            return [
                'publie' => false,
                'message' => 'Le résultat annuel sera disponible quand les trois trimestres seront programmés et finis.',
            ];
        }

        $tousLesTrimestresSontFermes = $trimestres
            ->every(fn ($trimestre) => $trimestre->estFerme());

        if (! $tousLesTrimestresSontFermes) {
            return [
                'publie' => false,
                'message' => 'Le résultat annuel sera affiché à la fin des trois trimestres.',
            ];
        }

        $tousLesTrimestresSontPublies = $resultatsTrimestres
            ->every(fn ($resultatTrimestre) => $resultatTrimestre['publie']);

        if (! $tousLesTrimestresSontPublies) {
            return [
                'publie' => false,
                'message' => 'Le résultat annuel sera disponible quand tous les trimestres seront complets.',
            ];
        }

        $moyennes = $resultatsTrimestres
            ->pluck('moyenne')
            ->filter(fn ($moyenne) => $moyenne !== null)
            ->values();

        if ($moyennes->count() !== 3) {
            return [
                'publie' => false,
                'message' => 'Le résultat annuel est incomplet.',
            ];
        }

        $moyenneAnnuelle = round($moyennes->avg(), 2);

        return [
            'publie' => true,
            'moyenne' => $moyenneAnnuelle,
            'rang' => $this->calculerRangAnnuelEleveDansClasse(
                $inscription,
                $trimestres,
                $totalCoefficientsClasse
            ),
            'appreciation' => $this->appreciationMoyenne($moyenneAnnuelle),
            'decision' => $moyenneAnnuelle >= 10 ? 'Passe' : 'Redouble',
            'message' => null,
        ];
    }

    /**
     * Calcule la moyenne trimestrielle d'une inscription.
     */
    private function calculerMoyenneInscriptionTrimestre(
        Inscription $inscription,
        Trimestre $trimestre,
        float $totalCoefficientsClasse
    ): ?float {
        $inscription->loadMissing('notes.evaluation');

        if ($totalCoefficientsClasse <= 0) {
            return null;
        }

        $totalPoints = $this->resultatTrimestrielService->calculerTotalPondereParMatiere(
            $inscription->notes,
            $trimestre->id
        );

        return $this->resultatTrimestrielService->appliquerRetenues(
            $inscription->id,
            $trimestre->id,
            $totalPoints,
            $totalCoefficientsClasse
        )['moyenne_finale'];
    }

    /**
     * Calcule le rang de l'élève dans sa classe pour un trimestre.
     */
    private function calculerRangEleveDansClasse(
        Inscription $inscriptionEleve,
        Trimestre $trimestre,
        ?float $moyenneEleve,
        float $totalCoefficientsClasse
    ): ?int {
        if ($moyenneEleve === null) {
            return null;
        }

        $inscriptionsClasse = Inscription::with([
            'eleve',
            'notes.evaluation',
        ])
            ->where('classe_id', $inscriptionEleve->classe_id)
            ->where('annee_scolaire_id', $inscriptionEleve->annee_scolaire_id)
            ->whereIn('statut', ['actif', 'termine'])
            ->get();

        $resultats = $inscriptionsClasse
            ->map(function ($inscription) use ($trimestre, $totalCoefficientsClasse) {
                return [
                    'inscription_id' => $inscription->id,
                    'moyenne' => $this->calculerMoyenneInscriptionTrimestre(
                        $inscription,
                        $trimestre,
                        $totalCoefficientsClasse
                    ),
                ];
            })
            ->filter(function ($resultat) {
                return $resultat['moyenne'] !== null;
            })
            ->sortByDesc('moyenne')
            ->values();

        $rang = 0;
        $position = 0;
        $moyennePrecedente = null;

        foreach ($resultats as $resultat) {
            $position++;

            if ($moyennePrecedente === null || $resultat['moyenne'] !== $moyennePrecedente) {
                $rang = $position;
            }

            if ((int) $resultat['inscription_id'] === (int) $inscriptionEleve->id) {
                return $rang;
            }

            $moyennePrecedente = $resultat['moyenne'];
        }

        return null;
    }

    private function calculerRangAnnuelEleveDansClasse(
        Inscription $inscriptionEleve,
        $trimestres,
        float $totalCoefficientsClasse
    ): ?int {
        $inscriptionsClasse = Inscription::with([
            'eleve',
            'notes.evaluation',
        ])
            ->where('classe_id', $inscriptionEleve->classe_id)
            ->where('annee_scolaire_id', $inscriptionEleve->annee_scolaire_id)
            ->whereIn('statut', ['actif', 'termine'])
            ->get();

        $resultats = $inscriptionsClasse
            ->map(function ($inscription) use ($trimestres, $totalCoefficientsClasse) {
                $moyennes = collect();

                foreach ($trimestres as $trimestre) {
                    $moyenne = $this->calculerMoyenneInscriptionTrimestre(
                        $inscription,
                        $trimestre,
                        $totalCoefficientsClasse
                    );

                    if ($moyenne === null) {
                        return [
                            'inscription_id' => $inscription->id,
                            'moyenne_annuelle' => null,
                        ];
                    }

                    $moyennes->push($moyenne);
                }

                return [
                    'inscription_id' => $inscription->id,
                    'moyenne_annuelle' => round($moyennes->avg(), 2),
                ];
            })
            ->filter(fn ($resultat) => $resultat['moyenne_annuelle'] !== null)
            ->sortByDesc('moyenne_annuelle')
            ->values();

        $rang = 0;
        $position = 0;
        $moyennePrecedente = null;

        foreach ($resultats as $resultat) {
            $position++;

            if ($moyennePrecedente === null || $resultat['moyenne_annuelle'] !== $moyennePrecedente) {
                $rang = $position;
            }

            if ((int) $resultat['inscription_id'] === (int) $inscriptionEleve->id) {
                return $rang;
            }

            $moyennePrecedente = $resultat['moyenne_annuelle'];
        }

        return null;
    }

    /**
     * Calcule le total des coefficients des matières affectées à une classe.
     */
    private function totalCoefficientsClasse(int $classeId): float
    {
        return $this->resultatTrimestrielService->totalCoefficientsClasse($classeId);
    }

    /**
     * Donne une appréciation à partir d'une moyenne sur 20.
     */
    private function appreciationMoyenne(float $moyenne): string
    {
        if ($moyenne >= 16) {
            return 'Très bien';
        }

        if ($moyenne >= 14) {
            return 'Bien';
        }

        if ($moyenne >= 12) {
            return 'Assez bien';
        }

        if ($moyenne >= 10) {
            return 'Passable';
        }

        if ($moyenne >= 7) {
            return 'Insuffisant';
        }

        return 'Très insuffisant';
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(Eleve $eleve)
    {
        return view('eleves.edit', compact('eleve'));
    }

    /**
     * Met à jour un élève.
     */
    public function update(Request $request, Eleve $eleve)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['required', 'in:M,F'],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'contact_parent' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            if ($eleve->photo) {
                Storage::disk('public')->delete($eleve->photo);
            }

            $validated['photo'] = $request->file('photo')->store('eleves/photos', 'public');
        }

        $eleve->update($validated);

        return redirect()
            ->route('eleves.index')
            ->with('success', 'Élève modifié avec succès.');
    }

    /**
     * Supprime logiquement un élève.
     */
    public function destroy(Eleve $eleve)
    {
        $eleve->update([
            'is_deleted' => true,
        ]);

        $eleve->delete();

        return redirect()
            ->route('eleves.index')
            ->with('success', 'Élève supprimé avec succès.');
    }

    /**
     * Génère automatiquement le matricule d'un élève.
     */
    private function genererMatriculeEleve(): string
    {
        $plusGrandNumero = Eleve::withTrashed()
            ->whereNotNull('matricule')
            ->where('matricule', 'like', 'ELV-%')
            ->pluck('matricule')
            ->map(function ($matricule) {
                if (preg_match('/^ELV-(\d+)$/', $matricule, $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter(fn ($numero) => $numero !== null)
            ->max() ?? 0;

        $numero = $plusGrandNumero + 1;
        $matricule = 'ELV-'.str_pad($numero, 4, '0', STR_PAD_LEFT);

        while (Eleve::withTrashed()->where('matricule', $matricule)->exists()) {
            $numero++;
            $matricule = 'ELV-'.str_pad($numero, 4, '0', STR_PAD_LEFT);
        }

        return $matricule;
    }
}
