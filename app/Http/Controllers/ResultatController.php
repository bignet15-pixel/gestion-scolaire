<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\Inscription;
use App\Models\Trimestre;
use App\Services\ResultatTrimestrielService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResultatController extends Controller
{
    public function __construct(
        private ResultatTrimestrielService $resultatTrimestrielService
    ) {}

    /**
     * Affiche les résultats trimestriels ou annuels.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $selectedAnneeId = $request->input('annee_scolaire_id');
        $selectedClasseId = $request->input('classe_id');
        $selectedPeriode = $request->input('periode');

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        if (! $selectedAnneeId && $annees->isNotEmpty()) {
            $selectedAnneeId = $this->anneeScolaireCourante()?->id ?? $annees->first()->id;
        }

        $classesQuery = Classe::with('anneeScolaire')
            ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                $query->where('annee_scolaire_id', $selectedAnneeId);
            })
            ->orderBy('niveau')
            ->orderBy('nom');

        if ($user->estEnseignant()) {
            $classeIds = ClasseMatiereUser::where('user_id', $user->id)
                ->whereIn('statut', ['actif', 'termine'])
                ->when($selectedAnneeId, function ($query) use ($selectedAnneeId) {
                    $query->whereHas('classe', function ($q) use ($selectedAnneeId) {
                        $q->where('annee_scolaire_id', $selectedAnneeId);
                    });
                })
                ->pluck('classe_id')
                ->unique();

            $classesQuery->whereIn('id', $classeIds);
        }

        $classes = $classesQuery->get();

        if (! $selectedClasseId && $classes->isNotEmpty()) {
            $selectedClasseId = $classes->first()->id;
        }

        $classe = $selectedClasseId
            ? $classes->first(fn ($classeOption) => (string) $classeOption->id === (string) $selectedClasseId)
            : null;

        if ($user->estEnseignant() && $classe) {
            $autorise = ClasseMatiereUser::where('user_id', $user->id)
                ->where('classe_id', $classe->id)
                ->whereIn('statut', ['actif', 'termine'])
                ->exists();

            if (! $autorise) {
                abort(403, 'Accès refusé.');
            }
        }

        $trimestres = collect();
        $trimestre = null;

        $classement = collect();
        $resultatsAnnuels = collect();

        $stats = [
            'moyenne_min' => null,
            'moyenne_max' => null,
            'moyenne_classe' => null,
            'nombre_total' => 0,
            'nombre_avec_moyenne' => 0,
            'nombre_sans_moyenne' => 0,
            'nombre_valides' => 0,
            'pourcentage_validation' => 0,
            'total_coefficients' => 0,
        ];

        if ($classe) {
            $totalCoefficientsClasse = $this->totalCoefficientsClasse($classe);

            $trimestres = Trimestre::where('annee_scolaire_id', $classe->annee_scolaire_id)
                ->orderBy('date_debut')
                ->get();

            if (! $selectedPeriode && $trimestres->isNotEmpty()) {
                $selectedPeriode = (string) $trimestres->first()->id;
            }

            if ($selectedPeriode === 'annuel') {
                $resultatsAnnuels = $this->calculerResultatsAnnuels(
                    $classe,
                    $trimestres,
                    $totalCoefficientsClasse
                );

                $stats = $this->calculerStatsDepuisResultats(
                    $resultatsAnnuels,
                    'moyenne_annuelle',
                    $totalCoefficientsClasse
                );
            } else {
                $trimestre = $trimestres->first(fn ($trimestreOption) => (string) $trimestreOption->id === (string) $selectedPeriode);

                if ($trimestre) {
                    $classement = $this->calculerResultatsTrimestriels(
                        $classe,
                        $trimestre,
                        $totalCoefficientsClasse
                    );

                    $stats = $this->calculerStatsDepuisResultats(
                        $classement,
                        'moyenne',
                        $totalCoefficientsClasse
                    );
                }
            }
        }

        return view('resultats.index', compact(
            'annees',
            'classes',
            'classe',
            'trimestres',
            'trimestre',
            'selectedAnneeId',
            'selectedClasseId',
            'selectedPeriode',
            'classement',
            'resultatsAnnuels',
            'stats'
        ));
    }

    /**
     * Calcule les résultats trimestriels d'une classe.
     */
    private function calculerResultatsTrimestriels(
        Classe $classe,
        Trimestre $trimestre,
        float $totalCoefficientsClasse
    ) {
        $inscriptions = Inscription::with([
            'eleve',
            'notes.evaluation.matiere',
        ])
            ->where('classe_id', $classe->id)
            ->where('annee_scolaire_id', $classe->annee_scolaire_id)
            ->where('statut', 'actif')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->select('inscriptions.*')
            ->get();

        $resultats = $inscriptions->map(function ($inscription) use ($trimestre, $totalCoefficientsClasse) {
            $details = $this->calculerResultatInscriptionTrimestre(
                $inscription,
                $trimestre,
                $totalCoefficientsClasse
            );
            $moyenne = $details['moyenne_finale'];

            return [
                'inscription' => $inscription,
                'moyenne' => $moyenne,
                'moyenne_finale' => $moyenne,
                'moyenne_avant_sanction' => $details['moyenne_avant_sanction'],
                'total_pondere' => $details['total_pondere'],
                'total_points_en_moins' => $details['total_points_en_moins'],
                'total_pondere_final' => $details['total_pondere_final'],
                'total_coefficients' => $details['total_coefficients'],
                'appreciation' => $moyenne !== null
                    ? $this->appreciationMoyenne($moyenne)
                    : '-',
                'nombre_notes' => $inscription->notes
                    ->filter(function ($note) use ($trimestre) {
                        return $note->evaluation
                            && (int) $note->evaluation->trimestre_id === (int) $trimestre->id;
                    })
                    ->count(),
            ];
        });

        $resultats = $resultats
            ->sortByDesc(function ($resultat) {
                return $resultat['moyenne'] ?? -1;
            })
            ->values();

        return $this->attribuerRangs($resultats);
    }

    /**
     * Calcule les résultats annuels d'une classe.
     */
    private function calculerResultatsAnnuels(
        Classe $classe,
        $trimestres,
        float $totalCoefficientsClasse
    ) {
        $inscriptions = Inscription::with([
            'eleve',
            'notes.evaluation',
        ])
            ->where('classe_id', $classe->id)
            ->where('annee_scolaire_id', $classe->annee_scolaire_id)
            ->where('statut', 'actif')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->select('inscriptions.*')
            ->get();

        $resultats = $inscriptions->map(function ($inscription) use ($trimestres, $totalCoefficientsClasse) {
            $moyennesTrimestres = [];

            foreach ($trimestres as $trimestre) {
                $moyennesTrimestres[$trimestre->id] = $this->calculerMoyenneInscriptionTrimestre(
                    $inscription,
                    $trimestre,
                    $totalCoefficientsClasse
                );
            }

            $moyennesValides = collect($moyennesTrimestres)
                ->filter(function ($moyenne) {
                    return $moyenne !== null;
                });

            $resultatComplet = $trimestres->count() === 3
                && $moyennesValides->count() === 3;

            $moyenneAnnuelle = $resultatComplet
                ? round($moyennesValides->avg(), 2)
                : null;

            return [
                'inscription' => $inscription,
                'moyennes_trimestres' => $moyennesTrimestres,
                'moyenne_annuelle' => $moyenneAnnuelle,
                'resultat_complet' => $resultatComplet,
                'decision' => $this->decisionAnnuelle($moyenneAnnuelle, $resultatComplet),
            ];
        });

        $resultats = $resultats
            ->sortByDesc(function ($resultat) {
                return $resultat['moyenne_annuelle'] ?? -1;
            })
            ->values();

        return $this->attribuerRangsAnnuels($resultats);
    }

    /**
     * Calcule la moyenne trimestrielle d'une inscription.
     */
    private function calculerMoyenneInscriptionTrimestre(
        Inscription $inscription,
        Trimestre $trimestre,
        float $totalCoefficientsClasse
    ): ?float {
        return $this->calculerResultatInscriptionTrimestre(
            $inscription,
            $trimestre,
            $totalCoefficientsClasse
        )['moyenne_finale'];
    }

    private function calculerResultatInscriptionTrimestre(
        Inscription $inscription,
        Trimestre $trimestre,
        float $totalCoefficientsClasse
    ): array {
        $inscription->loadMissing('notes.evaluation');

        $totalPoints = $this->resultatTrimestrielService->calculerTotalPondereParMatiere(
            $inscription->notes,
            $trimestre->id
        );

        return $this->resultatTrimestrielService->appliquerRetenues(
            $inscription->id,
            $trimestre->id,
            $totalPoints,
            $totalCoefficientsClasse
        );
    }

    /**
     * Calcule les statistiques d'une classe à partir des résultats.
     */
    private function calculerStatsDepuisResultats(
        $resultats,
        string $cleMoyenne,
        float $totalCoefficientsClasse
    ): array {
        $moyennes = $resultats
            ->pluck($cleMoyenne)
            ->filter(function ($moyenne) {
                return $moyenne !== null;
            })
            ->values();

        $nombreTotal = $resultats->count();

        $nombreResultatsCalcules = $moyennes->count();

        $nombreAvecMoyenne = $moyennes
            ->filter(function ($moyenne) {
                return $moyenne >= 10;
            })
            ->count();

        $nombreSansMoyenne = $moyennes
            ->filter(function ($moyenne) {
                return $moyenne < 10;
            })
            ->count();

        return [
            'moyenne_min' => $nombreResultatsCalcules > 0 ? round($moyennes->min(), 2) : null,
            'moyenne_max' => $nombreResultatsCalcules > 0 ? round($moyennes->max(), 2) : null,
            'moyenne_classe' => $nombreResultatsCalcules > 0 ? round($moyennes->avg(), 2) : null,
            'nombre_total' => $nombreTotal,
            'nombre_avec_moyenne' => $nombreAvecMoyenne,
            'nombre_sans_moyenne' => $nombreSansMoyenne,
            'nombre_valides' => $nombreResultatsCalcules,
            'pourcentage_validation' => $nombreResultatsCalcules > 0
                ? round(($nombreAvecMoyenne / $nombreResultatsCalcules) * 100, 2)
                : 0,
            'total_coefficients' => round($totalCoefficientsClasse, 2),
        ];
    }

    /**
     * Calcule le total des coefficients des matières affectées à une classe.
     */
    private function totalCoefficientsClasse(Classe $classe): float
    {
        return $this->resultatTrimestrielService->totalCoefficientsClasse($classe->id);
    }

    /**
     * Attribue les rangs trimestriels.
     */
    private function attribuerRangs($resultats)
    {
        $classement = collect();

        $rang = 0;
        $position = 0;
        $moyennePrecedente = null;

        foreach ($resultats as $resultat) {
            $position++;

            if ($resultat['moyenne'] === null) {
                $resultat['rang'] = null;
                $classement->push($resultat);

                continue;
            }

            if ($moyennePrecedente === null || $resultat['moyenne'] !== $moyennePrecedente) {
                $rang = $position;
            }

            $resultat['rang'] = $rang;

            $moyennePrecedente = $resultat['moyenne'];

            $classement->push($resultat);
        }

        return $classement;
    }

    /**
     * Attribue les rangs annuels.
     */
    private function attribuerRangsAnnuels($resultats)
    {
        $classement = collect();

        $rang = 0;
        $position = 0;
        $moyennePrecedente = null;

        foreach ($resultats as $resultat) {
            $position++;

            if ($resultat['moyenne_annuelle'] === null) {
                $resultat['rang_annuel'] = null;
                $classement->push($resultat);

                continue;
            }

            if ($moyennePrecedente === null || $resultat['moyenne_annuelle'] !== $moyennePrecedente) {
                $rang = $position;
            }

            $resultat['rang_annuel'] = $rang;

            $moyennePrecedente = $resultat['moyenne_annuelle'];

            $classement->push($resultat);
        }

        return $classement;
    }

    /**
     * Appréciation d'une moyenne sur 20.
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
     * Décision annuelle.
     */
    private function decisionAnnuelle(?float $moyenneAnnuelle, bool $resultatComplet): string
    {
        if (! $resultatComplet) {
            return 'Résultat incomplet';
        }

        if ($moyenneAnnuelle >= 10) {
            return 'Passe';
        }

        return 'Redouble';
    }

    private function anneeScolaireCourante(): ?AnneeScolaire
    {
        return AnneeScolaire::where('statut', 'active')
            ->orderByDesc('date_debut')
            ->first()
            ?? AnneeScolaire::orderByDesc('date_debut')->first();
    }
}
