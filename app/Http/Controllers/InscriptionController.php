<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\Eleve;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Trimestre;

class InscriptionController extends Controller
{
    /**
     * Affiche la liste des inscriptions avec filtres année / classe
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

        $inscriptions = Inscription::with([
                'eleve',
                'classe.anneeScolaire',
                'anneeScolaire',
                'paiements',
                'notes',
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
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->orderByDesc('inscriptions.date_inscription')
            ->orderBy('eleves.nom')
            ->orderBy('eleves.prenom')
            ->select('inscriptions.*')
            ->get();

        return view('inscriptions.index', compact(
            'inscriptions',
            'annees',
            'classes',
            'selectedAnneeId',
            'selectedClasseId',
            'search'
        ));
    }
    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        $eleves = Eleve::orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $classes = Classe::with('anneeScolaire')
            ->orderBy('annee_scolaire_id')
            ->orderBy('niveau')
            ->orderBy('nom')
            ->get();

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('inscriptions.create', compact('eleves', 'classes', 'annees'));
    }

    /**
     * Enregistre une inscription.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'eleve_id' => ['required', 'exists:eleves,id'],
            'classe_id' => ['required', 'exists:classes,id'],
            'annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],
            'date_inscription' => ['required', 'date'],
            'frais_attendu' => ['nullable', 'numeric', 'min:0'],
            'statut' => ['required', 'in:actif,termine,abandonne,transfere'],
        ]);

        $classe = Classe::findOrFail($validated['classe_id']);

        $eleve = Eleve::findOrFail($validated['eleve_id']);

        $annee = AnneeScolaire::findOrFail($validated['annee_scolaire_id']);

        if ($annee->estFermee()) {
            return back()
                ->withErrors([
                    'annee_scolaire_id' => 'Impossible de créer une inscription dans une année scolaire fermée.',
                ])
                ->withInput();
        }

        $erreurProgression = $this->verifierProgressionScolaire($eleve, $classe, $annee);

        if ($erreurProgression !== null) {
            return back()
                ->withErrors([
                    'classe_id' => $erreurProgression,
                ])
                ->withInput();
        }

        if ((int) $classe->annee_scolaire_id !== (int) $annee->id) {
            return back()
                ->withErrors([
                    'classe_id' => 'La classe choisie n’appartient pas à l’année scolaire sélectionnée.',
                ])
                ->withInput();
        }

        if ((int) $classe->annee_scolaire_id !== (int) $validated['annee_scolaire_id']) {
            return back()
                ->withErrors([
                    'classe_id' => 'La classe choisie n’appartient pas à cette année scolaire.',
                ])
                ->withInput();
        }

        $existeDeja = Inscription::where('eleve_id', $validated['eleve_id'])
            ->where('annee_scolaire_id', $validated['annee_scolaire_id'])
            ->exists();

        if ($existeDeja) {
            return back()
                ->withErrors([
                    'eleve_id' => 'Cet élève est déjà inscrit pour cette année scolaire.',
                ])
                ->withInput();
        }

        if (empty($validated['frais_attendu'])) {
            $validated['frais_attendu'] = $classe->frais_scolarite;
        }

        

        Inscription::create($validated);

        return redirect()
            ->route('inscriptions.index')
            ->with('success', 'Inscription créée avec succès.');
    }

    /**
     * Affiche le détail d’une inscription.
     */
    public function show(Inscription $inscription)
    {
        $inscription->load([
            'eleve',
            'classe.anneeScolaire',
            'anneeScolaire',
            'paiements.gestionnaire',
            'notes.evaluation.matiere',
            'notes.evaluation.trimestre',
        ]);

        return view('inscriptions.show', compact('inscription'));
    }

    /**
     * Affiche le formulaire de modification.
     */
    public function edit(Inscription $inscription)
    {
        $eleves = Eleve::orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $classes = Classe::with('anneeScolaire')
            ->orderBy('annee_scolaire_id')
            ->orderBy('niveau')
            ->orderBy('nom')
            ->get();

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('inscriptions.edit', compact(
            'inscription',
            'eleves',
            'classes',
            'annees'
        ));
    }

    /**
     * Met à jour une inscription.
     */
    public function update(Request $request, Inscription $inscription)
    {
        if ($inscription->anneeScolaire?->estFermee()) {
            return back()->withErrors([
                'inscription' => 'Impossible de modifier cette inscription : son année scolaire est fermée.',
            ]);
        }

        $validated = $request->validate([
            'eleve_id' => ['required', 'exists:eleves,id'],
            'classe_id' => ['required', 'exists:classes,id'],
            'annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],
            'date_inscription' => ['required', 'date'],
            'frais_attendu' => ['required', 'numeric', 'min:0'],
            'statut' => ['required', 'in:actif,termine,abandonne,transfere'],
        ]);

        $classe = Classe::findOrFail($validated['classe_id']);

        $eleve = Eleve::findOrFail($validated['eleve_id']);

        $annee = AnneeScolaire::findOrFail($validated['annee_scolaire_id']);

        if ($annee->estFermee()) {
            return back()
                ->withErrors([
                    'annee_scolaire_id' => 'Impossible de déplacer cette inscription vers une année scolaire fermée.',
                ])
                ->withInput();
        }

        if ((int) $classe->annee_scolaire_id !== (int) $annee->id) {
            return back()
                ->withErrors([
                    'classe_id' => 'La classe choisie n’appartient pas à l’année scolaire sélectionnée.',
                ])
                ->withInput();
        }

        $erreurProgression = $this->verifierProgressionScolaire($eleve, $classe, $annee, $inscription->id);

        if ($erreurProgression !== null) {
            return back()
                ->withErrors([
                    'classe_id' => $erreurProgression,
                ])
                ->withInput();
        }

        if ((int) $classe->annee_scolaire_id !== (int) $validated['annee_scolaire_id']) {
            return back()
                ->withErrors([
                    'classe_id' => 'La classe choisie n’appartient pas à cette année scolaire.',
                ])
                ->withInput();
        }

        $existeDeja = Inscription::where('eleve_id', $validated['eleve_id'])
            ->where('annee_scolaire_id', $validated['annee_scolaire_id'])
            ->where('id', '!=', $inscription->id)
            ->exists();

        if ($existeDeja) {
            return back()
                ->withErrors([
                    'eleve_id' => 'Cet élève est déjà inscrit pour cette année scolaire.',
                ])
                ->withInput();
        }

        $inscription->update($validated);

        return redirect()
            ->route('inscriptions.index')
            ->with('success', 'Inscription modifiée avec succès.');
    }

    /**
     * Supprime logiquement une inscription si elle n'a pas encore de paiements ou de notes.
     */
    public function destroy(Inscription $inscription)
    {
        if ($inscription->anneeScolaire?->estFermee()) {
            return back()->withErrors([
                'inscription' => 'Impossible de supprimer cette inscription : son année scolaire est fermée.',
            ]);
        }

        $aDesPaiements = $inscription->paiements()->exists();

        $aDesNotes = $inscription->notes()->exists();

        if ($aDesPaiements || $aDesNotes) {
            return redirect()
                ->route('inscriptions.index')
                ->withErrors([
                    'inscription' => 'Impossible de supprimer cette inscription : elle contient déjà des paiements ou des notes. Modifiez plutôt son statut.',
                ]);
        }

        $inscription->update([
            'is_deleted' => true,
        ]);

        $inscription->delete();

        return redirect()
            ->route('inscriptions.index')
            ->with('success', 'Inscription supprimée avec succès.');
    }

    /**
     * Vérifie si un élève peut être inscrit dans une classe donnée
     * selon son résultat annuel précédent.
     */
    private function verifierProgressionScolaire(
        Eleve $eleve,
        Classe $classeDemandee,
        AnneeScolaire $anneeDemandee,
        ?int $inscriptionIgnoreeId = null
    ): ?string {
        $niveaux = [
            'CP1' => 1,
            'CP2' => 2,
            'CE1' => 3,
            'CE2' => 4,
            'CM1' => 5,
            'CM2' => 6,
        ];

        if (! isset($niveaux[$classeDemandee->niveau])) {
            return 'Le niveau de la classe demandée est invalide.';
        }

        $dejaInscritCetteAnnee = Inscription::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeDemandee->id)
            ->when($inscriptionIgnoreeId, function ($query) use ($inscriptionIgnoreeId) {
                $query->where('id', '!=', $inscriptionIgnoreeId);
            })
            ->exists();

        if ($dejaInscritCetteAnnee) {
            return 'Cet élève possède déjà une inscription pour cette année scolaire.';
        }

        $ancienneInscription = Inscription::with([
                'classe.anneeScolaire',
                'anneeScolaire',
                'notes.evaluation',
            ])
            ->where('eleve_id', $eleve->id)
            ->whereHas('anneeScolaire', function ($query) use ($anneeDemandee) {
                $query->where('date_debut', '<', $anneeDemandee->date_debut);
            })
            ->when($inscriptionIgnoreeId, function ($query) use ($inscriptionIgnoreeId) {
                $query->where('id', '!=', $inscriptionIgnoreeId);
            })
            ->orderByDesc(
                AnneeScolaire::select('date_debut')
                    ->whereColumn('annee_scolaires.id', 'inscriptions.annee_scolaire_id')
                    ->limit(1)
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Première inscription
        |--------------------------------------------------------------------------
        |
        | Si l’élève n’a jamais été inscrit avant, on autorise.
        |
        */

        if (! $ancienneInscription) {
            return null;
        }

        if ($ancienneInscription->resteAPayer() > 0) {
            return 'Impossible d’inscrire cet élève : il a encore des impayés sur son ancienne inscription.';
        }

        $niveauAncien = $ancienneInscription->classe?->niveau;

        if (! isset($niveaux[$niveauAncien])) {
            return 'L’ancienne classe de l’élève possède un niveau invalide.';
        }

        $rangAncienNiveau = $niveaux[$niveauAncien];

        $rangNiveauDemande = $niveaux[$classeDemandee->niveau];

        /*
        |--------------------------------------------------------------------------
        | Redoublement
        |--------------------------------------------------------------------------
        |
        | Si l’élève reste au même niveau, on autorise.
        |
        */

        if ($rangNiveauDemande === $rangAncienNiveau) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Saut de classe interdit
        |--------------------------------------------------------------------------
        */

        if ($rangNiveauDemande > $rangAncienNiveau + 1) {
            return 'Impossible d’inscrire cet élève à ce niveau : le saut de classe n’est pas autorisé.';
        }

        /*
        |--------------------------------------------------------------------------
        | Retour en arrière interdit
        |--------------------------------------------------------------------------
        */

        if ($rangNiveauDemande < $rangAncienNiveau) {
            return 'Impossible d’inscrire cet élève dans un niveau inférieur à sa dernière classe.';
        }

        /*
        |--------------------------------------------------------------------------
        | Passage en classe supérieure
        |--------------------------------------------------------------------------
        |
        | Pour passer au niveau suivant, l’élève doit avoir validé l’année précédente.
        |
        */

        $moyenneAnnuelle = $this->calculerMoyenneAnnuelleInscription($ancienneInscription);

        if ($moyenneAnnuelle === null) {
            return 'Impossible d’inscrire cet élève en classe supérieure : son résultat annuel précédent est incomplet.';
        }

        if ($moyenneAnnuelle < 10) {
            return 'Impossible d’inscrire cet élève en classe supérieure : il n’a pas validé l’année précédente. Il doit redoubler le même niveau.';
        }

        return null;
    }

    /**
     * Calcule la moyenne annuelle d'une inscription.
     * Retourne null si les 3 trimestres ne sont pas complets.
     */
    private function calculerMoyenneAnnuelleInscription(Inscription $inscription): ?float
    {
        $trimestres = Trimestre::where('annee_scolaire_id', $inscription->annee_scolaire_id)
            ->orderBy('date_debut')
            ->get();

        if ($trimestres->count() !== 3) {
            return null;
        }

        $totalCoefficientsClasse = $this->totalCoefficientsClasse((int) $inscription->classe_id);

        $moyennes = collect();

        foreach ($trimestres as $trimestre) {
            $moyenneTrimestre = $this->calculerMoyenneInscriptionTrimestre(
                $inscription,
                $trimestre,
                $totalCoefficientsClasse
            );

            if ($moyenneTrimestre === null) {
                return null;
            }

            $moyennes->push($moyenneTrimestre);
        }

        return round($moyennes->avg(), 2);
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

        $totalPoints = 0;

        foreach ($inscription->notes as $note) {
            $evaluation = $note->evaluation;

            if (! $evaluation) {
                continue;
            }

            if ((int) $evaluation->trimestre_id !== (int) $trimestre->id) {
                continue;
            }

            if ((float) $evaluation->bareme <= 0) {
                continue;
            }

            $noteSur20 = ((float) $note->valeur / (float) $evaluation->bareme) * 20;

            $coefficient = (float) $evaluation->coefficient;

            $totalPoints += $noteSur20 * $coefficient;
        }

        return round($totalPoints / $totalCoefficientsClasse, 2);
    }

    /**
     * Calcule le total des coefficients des matières affectées à une classe.
     */
    private function totalCoefficientsClasse(int $classeId): float
    {
        return (float) ClasseMatiereUser::where('classe_id', $classeId)
            ->whereIn('statut', ['actif', 'termine'])
            ->sum('coefficient');
    }
}
