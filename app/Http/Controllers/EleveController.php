<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\AnneeScolaire;
use App\Models\ClasseMatiereUser;
use App\Models\Inscription;
use App\Models\Trimestre;

class EleveController extends Controller
{
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
        $selectedAnneeId = $request->input('annee_scolaire_id');
        $selectedClasseId = $request->input('classe_id');
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

        foreach ($inscriptions as $inscription) {
            $trimestres = Trimestre::where('annee_scolaire_id', $inscription->annee_scolaire_id)
                ->orderBy('date_debut')
                ->get();

            $totalCoefficientsClasse = $this->totalCoefficientsClasse((int) $inscription->classe_id);

            $resultatsTrimestres = collect();

            foreach ($trimestres as $trimestre) {
                $notes = $inscription->notes
                    ->filter(function ($note) use ($trimestre) {
                        return $note->evaluation
                            && (int) $note->evaluation->trimestre_id === (int) $trimestre->id;
                    })
                    ->values();

                $moyenne = $this->calculerMoyenneInscriptionTrimestre(
                    $inscription,
                    $trimestre,
                    $totalCoefficientsClasse
                );

                $rang = $this->calculerRangEleveDansClasse(
                    $inscription,
                    $trimestre,
                    $moyenne,
                    $totalCoefficientsClasse
                );

                $resultatsTrimestres->push([
                    'trimestre' => $trimestre,
                    'notes' => $notes,
                    'moyenne' => $moyenne,
                    'rang' => $rang,
                    'appreciation' => $moyenne !== null
                        ? $this->appreciationMoyenne($moyenne)
                        : '-',
                    'total_coefficients' => $totalCoefficientsClasse,
                ]);
            }

            $resultats->push([
                'inscription' => $inscription,
                'trimestres' => $resultatsTrimestres,
            ]);
        }

        return $resultats;
    }

    /**
     * Calcule la moyenne trimestrielle d'une inscription.
     */
    private function calculerMoyenneInscriptionTrimestre(
        Inscription $inscription,
        Trimestre $trimestre,
        float $totalCoefficientsClasse
    ): ?float
    {
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
            ->where('statut', 'actif')
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

    /**
     * Calcule le total des coefficients des matières affectées à une classe.
     */
    private function totalCoefficientsClasse(int $classeId): float
    {
        return (float) ClasseMatiereUser::where('classe_id', $classeId)
            ->whereIn('statut', ['actif', 'termine'])
            ->sum('coefficient');
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
        $dernier = Eleve::whereNotNull('matricule')
            ->where('matricule', 'like', 'ELV-%')
            ->orderByDesc('id')
            ->first();

        if (! $dernier) {
            return 'ELV-0001';
        }

        $numero = (int) str_replace('ELV-', '', $dernier->matricule);
        $numero++;

        return 'ELV-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}
