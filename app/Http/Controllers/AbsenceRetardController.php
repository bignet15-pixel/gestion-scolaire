<?php

namespace App\Http\Controllers;

use App\Models\AbsenceRetard;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\Inscription;
use App\Services\Assiduite\SanctionDetectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AbsenceRetardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $selectedAnneeId = $request->input('annee_scolaire_id')
            ?: $this->anneeScolaireCourante()?->id;
        $classes = $this->classesAccessibles($user, $selectedAnneeId, false);
        $selectedClasseId = $request->filled('classe_id')
            && $classes->contains(fn ($classe) => (string) $classe->id === (string) $request->input('classe_id'))
                ? $request->input('classe_id')
                : null;
        $selectedType = in_array($request->input('type'), AbsenceRetard::TYPES, true)
            ? $request->input('type')
            : null;
        $selectedStatut = in_array($request->input('statut'), AbsenceRetard::STATUTS, true)
            ? $request->input('statut')
            : null;
        $dateDebut = $this->dateFiltre($request->input('date_debut'));
        $dateFin = $this->dateFiltre($request->input('date_fin'));
        $classeIds = $classes->pluck('id');

        $evenements = AbsenceRetard::with([
            'inscription.eleve',
            'inscription.classe.anneeScolaire',
            'enregistrePar',
            'statutMisAJourPar',
        ])
            ->whereHas('inscription', function ($query) use ($classeIds, $selectedAnneeId) {
                $query->whereIn('classe_id', $classeIds);

                if ($selectedAnneeId) {
                    $query->where('annee_scolaire_id', $selectedAnneeId);
                }
            })
            ->when($selectedClasseId, function ($query) use ($selectedClasseId) {
                $query->whereHas('inscription', function ($q) use ($selectedClasseId) {
                    $q->where('classe_id', $selectedClasseId);
                });
            })
            ->when($selectedType, fn ($query) => $query->where('type', $selectedType))
            ->when($selectedStatut, fn ($query) => $query->where('statut', $selectedStatut))
            ->when($dateDebut, fn ($query) => $query->whereDate('date_debut', '>=', $dateDebut))
            ->when($dateFin, fn ($query) => $query->whereDate('date_debut', '<=', $dateFin))
            ->orderByDesc('date_debut')
            ->orderByDesc('created_at')
            ->get();

        $statistiques = [
            'absences' => $evenements->where('type', 'absence')->count(),
            'retards' => $evenements->where('type', 'retard')->count(),
            'en_attente' => $evenements->where('statut', 'en_attente')->count(),
            'non_justifiees' => $evenements
                ->whereIn('statut', ['non_justifiee', 'refusee'])
                ->count(),
        ];

        return view('absences_retards.index', compact(
            'evenements',
            'annees',
            'classes',
            'selectedAnneeId',
            'selectedClasseId',
            'selectedType',
            'selectedStatut',
            'dateDebut',
            'dateFin',
            'statistiques'
        ));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $selectedAnneeId = $request->input('annee_scolaire_id')
            ?: $this->anneeScolaireCourante()?->id;
        $selectedAnnee = $annees->first(
            fn ($annee) => (string) $annee->id === (string) $selectedAnneeId
        );
        $classes = $this->classesAccessibles($user, $selectedAnneeId, true);
        $selectedClasseId = $request->filled('classe_id')
            && $classes->contains(fn ($classe) => (string) $classe->id === (string) $request->input('classe_id'))
                ? $request->input('classe_id')
                : $classes->first()?->id;
        $inscriptions = $this->inscriptionsClasse($selectedAnneeId, $selectedClasseId);
        $selectedInscriptionId = $request->filled('inscription_id')
            && $inscriptions->contains(fn ($inscription) => (string) $inscription->id === (string) $request->input('inscription_id'))
                ? $request->input('inscription_id')
                : $inscriptions->first()?->id;

        return view('absences_retards.create', compact(
            'annees',
            'classes',
            'inscriptions',
            'selectedAnnee',
            'selectedAnneeId',
            'selectedClasseId',
            'selectedInscriptionId'
        ));
    }

    public function store(Request $request, SanctionDetectionService $detectionService)
    {
        $validated = $this->validerEvenement($request);
        $inscription = Inscription::with(['classe.anneeScolaire', 'eleve'])
            ->findOrFail($validated['inscription_id']);

        $this->verifierContexteInscription($inscription, $validated);
        $this->verifierAccesClasse((int) $inscription->classe_id, true);

        if (Auth::user()->estEnseignant()) {
            $validated['statut'] = 'en_attente';
        }

        $validated = $this->normaliserEvenement($validated);
        $this->verifierDoublon($validated);
        $validated['source_signalement'] = Auth::user()->estEnseignant()
            ? 'enseignant'
            : 'gestionnaire';
        $validated['enregistre_par'] = Auth::id();

        if ($validated['statut'] !== 'en_attente') {
            $validated['statut_mis_a_jour_par'] = Auth::id();
            $validated['statut_mis_a_jour_le'] = now();
        }

        if ($request->hasFile('piece_justificative')) {
            $validated['piece_justificative'] = $request
                ->file('piece_justificative')
                ->store('assiduite/justificatifs', 'public');
        }

        $evenement = AbsenceRetard::create($validated);
        $detectionService->detecter($evenement);

        return redirect()
            ->route('absences-retards.index', [
                'annee_scolaire_id' => $inscription->annee_scolaire_id,
                'classe_id' => $inscription->classe_id,
            ])
            ->with('success', 'Événement d’assiduité enregistré avec succès.');
    }

    public function show(AbsenceRetard $absence_retard)
    {
        $absence_retard->load([
            'inscription.eleve',
            'inscription.classe.anneeScolaire',
            'enregistrePar',
            'statutMisAJourPar',
        ]);
        $this->verifierAccesClasse((int) $absence_retard->inscription->classe_id, false);

        return view('absences_retards.show', ['evenement' => $absence_retard]);
    }

    public function edit(AbsenceRetard $absence_retard)
    {
        $this->verifierGestionnaire();
        $absence_retard->load([
            'inscription.eleve',
            'inscription.classe.anneeScolaire',
        ]);
        $this->verifierAnneeOuverte($absence_retard->inscription);

        return view('absences_retards.edit', ['evenement' => $absence_retard]);
    }

    public function update(
        Request $request,
        AbsenceRetard $absence_retard,
        SanctionDetectionService $detectionService
    ) {
        $this->verifierGestionnaire();
        $absence_retard->load('inscription.classe.anneeScolaire');
        $this->verifierAnneeOuverte($absence_retard->inscription);

        $validated = $this->validerEvenement($request, $absence_retard);
        $validated['inscription_id'] = $absence_retard->inscription_id;
        $this->verifierContexteInscription($absence_retard->inscription, $validated, false);
        $validated = $this->normaliserEvenement($validated);
        $this->verifierDoublon($validated, $absence_retard->id);

        if ($validated['statut'] !== $absence_retard->statut) {
            $validated['statut_mis_a_jour_par'] = Auth::id();
            $validated['statut_mis_a_jour_le'] = now();
        }

        if ($request->hasFile('piece_justificative')) {
            $ancienFichier = $absence_retard->piece_justificative;
            $validated['piece_justificative'] = $request
                ->file('piece_justificative')
                ->store('assiduite/justificatifs', 'public');

            if ($ancienFichier) {
                Storage::disk('public')->delete($ancienFichier);
            }
        } else {
            unset($validated['piece_justificative']);
        }

        $absence_retard->update($validated);
        $detectionService->detecter($absence_retard->fresh());

        return redirect()
            ->route('absences-retards.show', $absence_retard)
            ->with('success', 'Événement d’assiduité modifié avec succès.');
    }

    public function destroy(AbsenceRetard $absence_retard)
    {
        $this->verifierGestionnaire();
        $absence_retard->load('inscription.classe.anneeScolaire');
        $this->verifierAnneeOuverte($absence_retard->inscription);

        if ($absence_retard->piece_justificative) {
            Storage::disk('public')->delete($absence_retard->piece_justificative);
        }

        $absence_retard->delete();

        return redirect()
            ->route('absences-retards.index')
            ->with('success', 'Événement d’assiduité supprimé avec succès.');
    }

    private function validerEvenement(Request $request, ?AbsenceRetard $evenement = null): array
    {
        $rules = [
            'inscription_id' => [$evenement ? 'nullable' : 'required', 'exists:inscriptions,id'],
            'annee_scolaire_id' => [$evenement ? 'nullable' : 'required', 'exists:annee_scolaires,id'],
            'classe_id' => [$evenement ? 'nullable' : 'required', 'exists:classes,id'],
            'type' => ['required', Rule::in(AbsenceRetard::TYPES)],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'periode' => ['required', Rule::in(AbsenceRetard::PERIODES)],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'heure_fin' => ['nullable', 'date_format:H:i', 'after:heure_debut'],
            'heure_arrivee' => ['nullable', 'date_format:H:i'],
            'duree_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'categorie_motif' => ['required', Rule::in(AbsenceRetard::CATEGORIES_MOTIF)],
            'motif' => ['nullable', 'string', 'max:2000'],
            'statut' => ['required', Rule::in(AbsenceRetard::STATUTS)],
            'justification' => ['nullable', 'string', 'max:3000'],
            'piece_justificative' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'commentaire_interne' => ['nullable', 'string', 'max:3000'],
            'visible_parent' => ['nullable', 'boolean'],
        ];

        $validated = $request->validate($rules);
        $validated['visible_parent'] = $request->boolean('visible_parent');

        $horairesRetardComplets = ! empty($validated['heure_debut'])
            && ! empty($validated['heure_arrivee']);

        if ($validated['type'] === 'retard'
            && empty($validated['duree_minutes'])
            && ! $horairesRetardComplets) {
            throw ValidationException::withMessages([
                'heure_arrivee' => 'Indiquez une durée en minutes ou renseignez l’heure prévue et l’heure d’arrivée.',
            ]);
        }

        if ($validated['type'] === 'absence'
            && $validated['periode'] === 'cours'
            && (empty($validated['heure_debut']) || empty($validated['heure_fin']))) {
            throw ValidationException::withMessages([
                'heure_debut' => 'Les heures de début et de fin sont obligatoires pour la période « cours ».',
            ]);
        }

        if ($validated['type'] === 'retard'
            && ! empty($validated['heure_debut'])
            && ! empty($validated['heure_arrivee'])
            && $validated['heure_arrivee'] <= $validated['heure_debut']) {
            throw ValidationException::withMessages([
                'heure_arrivee' => 'L’heure d’arrivée doit être postérieure à l’heure prévue.',
            ]);
        }

        return $validated;
    }

    private function normaliserEvenement(array $validated): array
    {
        $validated['date_fin'] = $validated['date_fin'] ?? $validated['date_debut'];

        if ($validated['type'] === 'absence') {
            $validated['heure_arrivee'] = null;
            $validated['duree_minutes'] = null;
        } else {
            $validated['date_fin'] = $validated['date_debut'];
            $validated['heure_fin'] = null;

            if (empty($validated['duree_minutes'])
                && ! empty($validated['heure_debut'])
                && ! empty($validated['heure_arrivee'])) {
                $heureDebut = Carbon::createFromFormat('H:i', $validated['heure_debut']);
                $heureArrivee = Carbon::createFromFormat('H:i', $validated['heure_arrivee']);
                $validated['duree_minutes'] = (int) round(
                    max(1, $heureDebut->diffInMinutes($heureArrivee, false))
                );
            }
        }

        unset($validated['annee_scolaire_id'], $validated['classe_id']);

        return $validated;
    }

    private function verifierContexteInscription(
        Inscription $inscription,
        array $validated,
        bool $verifierIds = true
    ): void {
        if ($verifierIds
            && ((int) $inscription->annee_scolaire_id !== (int) $validated['annee_scolaire_id']
                || (int) $inscription->classe_id !== (int) $validated['classe_id'])) {
            throw ValidationException::withMessages([
                'inscription_id' => 'L’élève ne correspond pas à la classe et à l’année scolaire sélectionnées.',
            ]);
        }

        $this->verifierAnneeOuverte($inscription);

        $debut = Carbon::parse($validated['date_debut'])->startOfDay();
        $fin = Carbon::parse($validated['date_fin'] ?? $validated['date_debut'])->startOfDay();
        $annee = $inscription->anneeScolaire;

        if (($annee?->date_debut && $debut->lt($annee->date_debut))
            || ($annee?->date_fin && $fin->gt($annee->date_fin))) {
            throw ValidationException::withMessages([
                'date_debut' => 'La période doit appartenir à l’année scolaire de l’inscription.',
            ]);
        }

        if ($inscription->date_inscription && $debut->lt($inscription->date_inscription)) {
            throw ValidationException::withMessages([
                'date_debut' => 'La période ne peut pas précéder la date d’inscription de l’élève.',
            ]);
        }
    }

    private function verifierDoublon(array $validated, ?int $ignoreId = null): void
    {
        $doublon = AbsenceRetard::query()
            ->where('inscription_id', $validated['inscription_id'])
            ->where('type', $validated['type'])
            ->whereDate('date_debut', $validated['date_debut'])
            ->whereDate('date_fin', $validated['date_fin'])
            ->where('periode', $validated['periode'])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($doublon) {
            throw ValidationException::withMessages([
                'date_debut' => 'Un événement identique existe déjà pour cet élève et cette période.',
            ]);
        }
    }

    private function classesAccessibles($user, $anneeId, bool $ecriture)
    {
        $query = Classe::with('anneeScolaire')
            ->when($anneeId, fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->orderBy('niveau')
            ->orderBy('nom');

        if ($user->estEnseignant()) {
            $statuts = $ecriture ? ['actif'] : ['actif', 'termine'];
            $classeIds = ClasseMatiereUser::where('user_id', $user->id)
                ->whereIn('statut', $statuts)
                ->pluck('classe_id')
                ->unique();

            $query->whereIn('id', $classeIds);
        }

        return $query->get();
    }

    private function inscriptionsClasse($anneeId, $classeId)
    {
        if (! $anneeId || ! $classeId) {
            return collect();
        }

        return Inscription::with('eleve')
            ->where('annee_scolaire_id', $anneeId)
            ->where('classe_id', $classeId)
            ->where('statut', 'actif')
            ->get()
            ->sortBy(fn ($inscription) => $inscription->eleve?->nom.' '.$inscription->eleve?->prenom)
            ->values();
    }

    private function verifierAccesClasse(int $classeId, bool $ecriture): void
    {
        $user = Auth::user();

        if ($user->estGestionnaire()) {
            return;
        }

        $statuts = $ecriture ? ['actif'] : ['actif', 'termine'];
        $autorise = ClasseMatiereUser::where('user_id', $user->id)
            ->where('classe_id', $classeId)
            ->whereIn('statut', $statuts)
            ->exists();

        abort_unless($autorise, 403, 'Accès refusé.');
    }

    private function verifierGestionnaire(): void
    {
        abort_unless(Auth::user()->estGestionnaire(), 403, 'Accès refusé.');
    }

    private function verifierAnneeOuverte(Inscription $inscription): void
    {
        $inscription->loadMissing('anneeScolaire');

        if ($inscription->anneeScolaire?->estFermee()) {
            throw ValidationException::withMessages([
                'annee_scolaire_id' => 'Cette année scolaire est fermée : l’assiduité est consultable uniquement en historique.',
            ]);
        }
    }

    private function anneeScolaireCourante(): ?AnneeScolaire
    {
        return AnneeScolaire::where('statut', 'active')
            ->orderByDesc('date_debut')
            ->first()
            ?? AnneeScolaire::orderByDesc('date_debut')->first();
    }

    private function dateFiltre($date): ?string
    {
        if (! is_string($date) || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
