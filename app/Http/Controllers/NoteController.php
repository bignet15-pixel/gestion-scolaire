<?php

namespace App\Http\Controllers;

use App\Models\ClasseMatiereUser;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Note;
use App\Services\NotificationScolaireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    /**
     * Affiche la page de saisie des notes pour une évaluation.
     */
    public function saisie(Evaluation $evaluation)
    {
        $this->verifierAccesEvaluation($evaluation);

        $evaluation->load([
            'classe.anneeScolaire',
            'matiere',
            'trimestre.anneeScolaire',
            'createur',
        ]);

        $inscriptions = Inscription::with([
            'eleve',
            'notes' => function ($query) use ($evaluation) {
                $query->where('evaluation_id', $evaluation->id);
            },
        ])
        ->where('classe_id', $evaluation->classe_id)
        ->where('annee_scolaire_id', $evaluation->trimestre->annee_scolaire_id)
        ->where('statut', 'actif')
        ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
        ->orderBy('eleves.nom')
        ->orderBy('eleves.prenom')
        ->select('inscriptions.*')
        ->get();

        $nombreEleves = $inscriptions->count();

        $nombreNotesSaisies = $inscriptions
            ->filter(function ($inscription) {
                $note = $inscription->notes->first();

                return $note && $note->valeur !== null && $note->valeur !== '';
            })
            ->count();

        $nombreNotesManquantes = $nombreEleves - $nombreNotesSaisies;

        $evaluationVerrouillee = $evaluation->trimestre?->estFerme()
            || $evaluation->classe?->anneeScolaire?->estFermee()
            || $evaluation->trimestre?->anneeScolaire?->estFermee();

        return view('notes.saisie', compact(
            'evaluation',
            'inscriptions',
            'nombreEleves',
            'nombreNotesSaisies',
            'nombreNotesManquantes',
            'evaluationVerrouillee'
        ));
    }

    /**
     * Enregistre ou modifie les notes d'une évaluation.
     */
    public function enregistrer(Request $request, Evaluation $evaluation, NotificationScolaireService $notificationScolaireService)
    {
        $this->verifierAccesEvaluation($evaluation);

        $evaluation->load([
            'classe.anneeScolaire',
            'trimestre.anneeScolaire',
        ]);

        if ($evaluation->trimestre?->estFerme()) {
            return back()->withErrors([
                'notes' => 'Impossible de modifier les notes : le trimestre est fermé.',
            ]);
        }

        if ($evaluation->classe?->anneeScolaire?->estFermee() || $evaluation->trimestre?->anneeScolaire?->estFermee()) {
            return back()->withErrors([
                'notes' => 'Impossible de modifier les notes : l’année scolaire est fermée.',
            ]);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'array'],
            'notes.*.valeur' => ['nullable', 'numeric', 'min:0', 'max:' . $evaluation->bareme],
        ]);

        $notes = $validated['notes'] ?? [];

        foreach ($notes as $inscriptionId => $data) {
            $inscription = Inscription::where('id', $inscriptionId)
                ->where('classe_id', $evaluation->classe_id)
                ->where('annee_scolaire_id', $evaluation->trimestre->annee_scolaire_id)
                ->first();

            if (! $inscription) {
                continue;
            }

            $valeur = $data['valeur'] ?? null;
            $appreciation = null;

            if ($valeur !== null && $valeur !== '') {
                $appreciation = $this->genererAppreciation(
                    (float) $valeur,
                    (float) $evaluation->bareme
                );
            }

            if ($valeur === null || $valeur === '') {
                Note::where('inscription_id', $inscription->id)
                    ->where('evaluation_id', $evaluation->id)
                    ->delete();

                continue;
            }

            $note = Note::withTrashed()
                ->where('inscription_id', $inscription->id)
                ->where('evaluation_id', $evaluation->id)
                ->first();

            if ($note) {
                if ($note->trashed()) {
                    $note->restore();
                }

                $note->update([
                    'valeur' => $valeur,
                    'appreciation' => $appreciation,
                    'is_deleted' => false,
                ]);
            } else {
                $note = Note::create([
                    'inscription_id' => $inscription->id,
                    'evaluation_id' => $evaluation->id,
                    'valeur' => $valeur,
                    'appreciation' => $appreciation,
                ]);
            }

            $notificationScolaireService->notifierNote($note->fresh([
                'inscription.eleve.parents',
                'evaluation.matiere',
                'evaluation.trimestre',
            ]));
        }

        return redirect()
            ->route('evaluations.show', $evaluation)
            ->with('success', 'Notes enregistrées avec succès.');
    }

    /**
     * Vérifie que l'utilisateur connecté peut gérer les notes de cette évaluation.
     */
    private function verifierAccesEvaluation(Evaluation $evaluation): void
    {
        $user = Auth::user();

        if ($user->estGestionnaire()) {
            return;
        }

        if ((int) $evaluation->user_id !== (int) $user->id) {
            abort(403, 'Vous ne pouvez saisir les notes que pour vos propres évaluations.');
        }

        $autorise = ClasseMatiereUser::where('user_id', $user->id)
            ->where('classe_id', $evaluation->classe_id)
            ->where('matiere_id', $evaluation->matiere_id)
            ->whereIn('statut', ['actif', 'termine'])
            ->exists();

        if (! $autorise) {
            abort(403, 'Accès refusé.');
        }
    }

    /**
     * Génère automatiquement une appréciation selon le pourcentage obtenu.
     */
    private function genererAppreciation(float $valeur, float $bareme): string
    {
        if ($bareme <= 0) {
            return 'Barème invalide';
        }

        $pourcentage = ($valeur / $bareme) * 100;

        if ($pourcentage >= 80) {
            return 'Très bien';
        }

        if ($pourcentage >= 70) {
            return 'Bien';
        }

        if ($pourcentage >= 60) {
            return 'Assez bien';
        }

        if ($pourcentage >= 50) {
            return 'Passable';
        }

        if ($pourcentage >= 35) {
            return 'Insuffisant';
        }

        return 'Très insuffisant';
    }
}
