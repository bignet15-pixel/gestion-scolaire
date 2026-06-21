<?php

namespace App\Services;

use App\Models\ClasseMatiereUser;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Trimestre;
use Illuminate\Support\Collection;
use RuntimeException;

class BulletinService
{
    public function __construct(
        private ResultatTrimestrielService $resultatTrimestrielService
    ) {}

    public function nombreEvaluationsAttendues(Inscription $inscription, Trimestre $trimestre): int
    {
        return $this->evaluationsAttendues($inscription, $trimestre)->count();
    }

    public function nombreNotesManquantes(Inscription $inscription, Trimestre $trimestre): int
    {
        $evaluationIds = $this->evaluationsAttendues($inscription, $trimestre)->pluck('id');

        if ($evaluationIds->isEmpty()) {
            return 0;
        }

        $nombreNotes = Note::where('inscription_id', $inscription->id)
            ->whereIn('evaluation_id', $evaluationIds)
            ->whereNotNull('valeur')
            ->count();

        return max(0, $evaluationIds->count() - $nombreNotes);
    }

    public function bulletinTrimestriel(Inscription $inscription, Trimestre $trimestre): array
    {
        $inscription->loadMissing([
            'eleve',
            'classe.anneeScolaire',
            'classe.enseignantPrincipal',
            'anneeScolaire',
        ]);

        if ((int) $inscription->annee_scolaire_id !== (int) $trimestre->annee_scolaire_id) {
            throw new RuntimeException('Ce trimestre ne correspond pas à l’année scolaire de cette inscription.');
        }

        if (! $trimestre->estFerme()) {
            throw new RuntimeException('Le bulletin trimestriel est disponible uniquement après la fermeture du trimestre.');
        }

        $evaluations = $this->evaluationsAttendues($inscription, $trimestre);

        if ($evaluations->isEmpty()) {
            throw new RuntimeException('Aucune évaluation n’est programmée pour ce trimestre.');
        }

        $notesManquantes = $this->nombreNotesManquantes($inscription, $trimestre);

        if ($notesManquantes > 0) {
            throw new RuntimeException('Le bulletin trimestriel est incomplet : '.$notesManquantes.' note(s) attendue(s) ne sont pas encore saisie(s).');
        }

        $lignes = $this->lignesTrimestrielles($inscription, $trimestre);
        $totalCoefficients = $this->totalCoefficientsClasse((int) $inscription->classe_id);
        $totalPondere = round($lignes->sum('points'), 2);
        $resultat = $this->resultatTrimestrielService->appliquerRetenues(
            $inscription->id,
            $trimestre->id,
            $totalPondere,
            $totalCoefficients
        );
        $moyenne = $resultat['moyenne_finale'];

        return [
            'inscription' => $inscription,
            'trimestre' => $trimestre,
            'lignes' => $lignes,
            'total_coefficients' => $resultat['total_coefficients'],
            'total_pondere' => $resultat['total_pondere'],
            'total_points_en_moins' => $resultat['total_points_en_moins'],
            'total_pondere_final' => $resultat['total_pondere_final'],
            'moyenne_avant_sanction' => $resultat['moyenne_avant_sanction'],
            'moyenne' => $moyenne,
            'moyenne_finale' => $moyenne,
            'rang' => $this->rangTrimestriel($inscription, $trimestre),
            'appreciation' => $moyenne !== null ? $this->appreciationMoyenne($moyenne) : '-',
            'effectif' => $this->inscriptionsClasse($inscription)->count(),
        ];
    }

    public function bulletinAnnuel(Inscription $inscription): array
    {
        $trimestres = Trimestre::where('annee_scolaire_id', $inscription->annee_scolaire_id)
            ->orderBy('date_debut')
            ->get();

        if ($trimestres->count() !== 3) {
            throw new RuntimeException('Le bulletin annuel nécessite trois trimestres programmés.');
        }

        if (! $trimestres->every(fn ($trimestre) => $trimestre->estFerme())) {
            throw new RuntimeException('Le bulletin annuel est disponible uniquement après la fermeture des trois trimestres.');
        }

        $bulletinsTrimestriels = $trimestres
            ->map(fn ($trimestre) => $this->bulletinTrimestriel($inscription, $trimestre));

        $moyennes = $bulletinsTrimestriels
            ->pluck('moyenne')
            ->filter(fn ($moyenne) => $moyenne !== null)
            ->values();

        if ($moyennes->count() !== 3) {
            throw new RuntimeException('Le bulletin annuel est incomplet.');
        }

        $moyenneAnnuelle = round($moyennes->avg(), 2);

        return [
            'inscription' => $inscription->loadMissing(['eleve', 'classe.anneeScolaire', 'anneeScolaire']),
            'trimestres' => $bulletinsTrimestriels,
            'moyenne_annuelle' => $moyenneAnnuelle,
            'rang_annuel' => $this->rangAnnuel($inscription, $trimestres),
            'appreciation' => $this->appreciationMoyenne($moyenneAnnuelle),
            'decision' => $moyenneAnnuelle >= 10 ? 'Passe' : 'Redouble',
            'effectif' => $this->inscriptionsClasse($inscription)->count(),
        ];
    }

    private function lignesTrimestrielles(Inscription $inscription, Trimestre $trimestre): Collection
    {
        $evaluations = $this->evaluationsAttendues($inscription, $trimestre);

        $notes = Note::with('evaluation.matiere')
            ->where('inscription_id', $inscription->id)
            ->whereIn('evaluation_id', $evaluations->pluck('id'))
            ->get()
            ->keyBy('evaluation_id');

        return $evaluations->map(function ($evaluation) use ($notes) {
            $note = $notes->get($evaluation->id);
            $noteSur20 = ((float) $note->valeur / (float) $evaluation->bareme) * 20;
            $coefficient = (float) $evaluation->coefficient;

            return [
                'evaluation' => $evaluation,
                'matiere' => $evaluation->matiere?->nom ?? '-',
                'type' => $evaluation->type,
                'note' => (float) $note->valeur,
                'bareme' => (float) $evaluation->bareme,
                'note_sur_20' => round($noteSur20, 2),
                'coefficient' => $coefficient,
                'points' => round($noteSur20 * $coefficient, 2),
                'appreciation' => $note->appreciation ?? '-',
            ];
        });
    }

    private function moyenneTrimestrielle(Inscription $inscription, Trimestre $trimestre): ?float
    {
        if (! $trimestre->estFerme()) {
            return null;
        }

        if ($this->nombreEvaluationsAttendues($inscription, $trimestre) === 0) {
            return null;
        }

        if ($this->nombreNotesManquantes($inscription, $trimestre) > 0) {
            return null;
        }

        $totalCoefficients = $this->totalCoefficientsClasse((int) $inscription->classe_id);

        if ($totalCoefficients <= 0) {
            return null;
        }

        $totalPondere = (float) $this->lignesTrimestrielles($inscription, $trimestre)->sum('points');

        return $this->resultatTrimestrielService->appliquerRetenues(
            $inscription->id,
            $trimestre->id,
            $totalPondere,
            $totalCoefficients
        )['moyenne_finale'];
    }

    private function rangTrimestriel(Inscription $inscriptionEleve, Trimestre $trimestre): ?int
    {
        $resultats = $this->inscriptionsClasse($inscriptionEleve)
            ->map(function ($inscription) use ($trimestre) {
                return [
                    'inscription_id' => $inscription->id,
                    'moyenne' => $this->moyenneTrimestrielle($inscription, $trimestre),
                ];
            })
            ->filter(fn ($resultat) => $resultat['moyenne'] !== null)
            ->sortByDesc('moyenne')
            ->values();

        return $this->rangDepuisResultats($resultats, 'moyenne', $inscriptionEleve->id);
    }

    private function rangAnnuel(Inscription $inscriptionEleve, Collection $trimestres): ?int
    {
        $resultats = $this->inscriptionsClasse($inscriptionEleve)
            ->map(function ($inscription) use ($trimestres) {
                $moyennes = collect();

                foreach ($trimestres as $trimestre) {
                    $moyenne = $this->moyenneTrimestrielle($inscription, $trimestre);

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

        return $this->rangDepuisResultats($resultats, 'moyenne_annuelle', $inscriptionEleve->id);
    }

    private function rangDepuisResultats(Collection $resultats, string $cleMoyenne, int $inscriptionId): ?int
    {
        $rang = 0;
        $position = 0;
        $moyennePrecedente = null;

        foreach ($resultats as $resultat) {
            $position++;

            if ($moyennePrecedente === null || $resultat[$cleMoyenne] !== $moyennePrecedente) {
                $rang = $position;
            }

            if ((int) $resultat['inscription_id'] === $inscriptionId) {
                return $rang;
            }

            $moyennePrecedente = $resultat[$cleMoyenne];
        }

        return null;
    }

    private function evaluationsAttendues(Inscription $inscription, Trimestre $trimestre): Collection
    {
        return Evaluation::with('matiere')
            ->where('classe_id', $inscription->classe_id)
            ->where('trimestre_id', $trimestre->id)
            ->orderBy('date_evaluation')
            ->orderBy('matiere_id')
            ->get();
    }

    private function inscriptionsClasse(Inscription $inscription): Collection
    {
        return Inscription::where('classe_id', $inscription->classe_id)
            ->where('annee_scolaire_id', $inscription->annee_scolaire_id)
            ->whereIn('statut', ['actif', 'termine'])
            ->get();
    }

    private function totalCoefficientsClasse(int $classeId): float
    {
        return (float) ClasseMatiereUser::where('classe_id', $classeId)
            ->whereIn('statut', ['actif', 'termine'])
            ->sum('coefficient');
    }

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
}
