<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\ClasseMatiereUser;
use App\Models\Inscription;
use App\Models\Sanction;
use App\Models\SanctionAppliquee;
use App\Models\Trimestre;
use App\Services\NotificationScolaireService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SanctionAppliqueeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $selectedAnneeId = $request->input('annee_scolaire_id')
            ?: $this->anneeScolaireCourante()?->id;
        $classes = $this->classesAccessibles($user, $selectedAnneeId);
        $selectedClasseId = $request->filled('classe_id')
            && $classes->contains(fn ($classe) => (string) $classe->id === (string) $request->input('classe_id'))
                ? $request->input('classe_id')
                : null;
        $selectedStatut = in_array($request->input('statut'), SanctionAppliquee::STATUTS, true)
            ? $request->input('statut')
            : null;
        $selectedOrigine = in_array($request->input('origine'), SanctionAppliquee::ORIGINES, true)
            ? $request->input('origine')
            : null;
        $classeIds = $classes->pluck('id');

        $sanctionsAppliquees = SanctionAppliquee::with([
            'inscription.eleve',
            'inscription.classe.anneeScolaire',
            'sanction',
            'trimestre',
            'appliquePar',
            'decisionPar',
        ])
            ->whereHas('inscription', function ($query) use ($classeIds, $selectedAnneeId) {
                $query->whereIn('classe_id', $classeIds);

                if ($selectedAnneeId) {
                    $query->where('annee_scolaire_id', $selectedAnneeId);
                }
            })
            ->when($selectedClasseId, function ($query) use ($selectedClasseId) {
                $query->whereHas('inscription', fn ($q) => $q->where('classe_id', $selectedClasseId));
            })
            ->when(
                $selectedStatut,
                fn ($query) => $query->where('statut', $selectedStatut),
                fn ($query) => $query->whereIn('statut', ['proposee', 'appliquee'])
            )            
            ->when($selectedOrigine, fn ($query) => $query->where('origine', $selectedOrigine))
            ->orderByRaw("CASE statut WHEN 'proposee' THEN 1 WHEN 'appliquee' THEN 2 WHEN 'terminee' THEN 3 WHEN 'ignoree' THEN 4 ELSE 5 END")
            ->orderByDesc('created_at')
            ->get();

        $statistiques = [
            'proposees' => $sanctionsAppliquees->where('statut', 'proposee')->count(),
            'appliquees' => $sanctionsAppliquees->where('statut', 'appliquee')->count(),
            'ignorees' => $sanctionsAppliquees->where('statut', 'ignoree')->count(),
            'cloturees' => $sanctionsAppliquees
                ->whereIn('statut', ['annulee', 'terminee'])
                ->count(),
        ];

        return view('sanctions_appliquees.index', compact(
            'sanctionsAppliquees',
            'annees',
            'classes',
            'selectedAnneeId',
            'selectedClasseId',
            'selectedStatut',
            'selectedOrigine',
            'statistiques'
        ));
    }

    public function create(Request $request)
    {
        $this->verifierGestionnaire();
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $selectedAnneeId = $request->input('annee_scolaire_id')
            ?: $this->anneeScolaireCourante()?->id;
        $selectedAnnee = $annees->first(
            fn ($annee) => (string) $annee->id === (string) $selectedAnneeId
        );
        $classes = $this->classesAccessibles(Auth::user(), $selectedAnneeId);
        $selectedClasseId = $request->filled('classe_id')
            && $classes->contains(fn ($classe) => (string) $classe->id === (string) $request->input('classe_id'))
                ? $request->input('classe_id')
                : $classes->first()?->id;
        $inscriptions = $this->inscriptionsClasse($selectedAnneeId, $selectedClasseId);
        $selectedInscriptionId = $request->filled('inscription_id')
            && $inscriptions->contains(fn ($inscription) => (string) $inscription->id === (string) $request->input('inscription_id'))
                ? $request->input('inscription_id')
                : $inscriptions->first()?->id;
        $sanctions = Sanction::query()
            ->where('active', true)
            ->whereIn('mode_declenchement', ['manuel', 'mixte'])
            ->orderByRaw("CASE categorie WHEN 'conduite' THEN 1 WHEN 'absence' THEN 2 WHEN 'retard' THEN 3 ELSE 4 END")
            ->orderBy('nom')
            ->get();
        $trimestres = Trimestre::where('annee_scolaire_id', $selectedAnneeId)
            ->orderBy('date_debut')
            ->get();

        return view('sanctions_appliquees.create', compact(
            'annees',
            'classes',
            'inscriptions',
            'sanctions',
            'trimestres',
            'selectedAnnee',
            'selectedAnneeId',
            'selectedClasseId',
            'selectedInscriptionId'
        ));
    }

    public function store(Request $request, NotificationScolaireService $notificationScolaireService)
    {
        $this->verifierGestionnaire();
        $validated = $request->validate([
            'annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],
            'classe_id' => ['required', 'exists:classes,id'],
            'inscription_id' => ['required', 'exists:inscriptions,id'],
            'sanction_id' => ['required', 'exists:sanctions,id'],
            'trimestre_id' => ['nullable', 'exists:trimestres,id'],
            'date_application' => ['nullable', 'date'],
            'periode_debut' => ['nullable', 'date'],
            'periode_fin' => ['nullable', 'date', 'after_or_equal:periode_debut'],
            'motif' => ['required', 'string', 'max:3000'],
            'commentaire_interne' => ['nullable', 'string', 'max:3000'],
            'visible_parent' => ['nullable', 'boolean'],
        ]);

        $inscription = Inscription::with('anneeScolaire')->findOrFail($validated['inscription_id']);
        $sanction = Sanction::findOrFail($validated['sanction_id']);
        $trimestre = ! empty($validated['trimestre_id'])
            ? Trimestre::findOrFail($validated['trimestre_id'])
            : null;

        if ((int) $inscription->annee_scolaire_id !== (int) $validated['annee_scolaire_id']
            || (int) $inscription->classe_id !== (int) $validated['classe_id']) {
            throw ValidationException::withMessages([
                'inscription_id' => 'L’élève ne correspond pas à la classe et à l’année sélectionnées.',
            ]);
        }

        if ($inscription->anneeScolaire?->estFermee()) {
            throw ValidationException::withMessages([
                'annee_scolaire_id' => 'Impossible d’appliquer une sanction dans une année scolaire fermée.',
            ]);
        }

        if (! $sanction->active) {
            throw ValidationException::withMessages([
                'sanction_id' => 'Cette sanction est désactivée.',
            ]);
        }

        if (! in_array($sanction->mode_declenchement, ['manuel', 'mixte'], true)) {
            throw ValidationException::withMessages([
                'sanction_id' => 'Une sanction strictement automatique ne peut pas être appliquée manuellement.',
            ]);
        }

        if ($sanction->type_effet === 'points_en_moins'
            && (float) $sanction->valeur_effet <= 0) {
            throw ValidationException::withMessages([
                'sanction_id' => 'Cette sanction doit définir un nombre de points strictement supérieur à zéro.',
            ]);
        }

        if ($trimestre && (int) $trimestre->annee_scolaire_id !== (int) $inscription->annee_scolaire_id) {
            throw ValidationException::withMessages([
                'trimestre_id' => 'Le trimestre ne correspond pas à l’année scolaire de l’inscription.',
            ]);
        }

        if ($sanction->type_effet === 'points_en_moins' && ! $trimestre) {
            throw ValidationException::withMessages([
                'trimestre_id' => 'Le trimestre est obligatoire pour une sanction retirant des points.',
            ]);
        }

        if ($sanction->type_effet === 'points_en_moins' && $trimestre?->estFerme()) {
            throw ValidationException::withMessages([
                'trimestre_id' => 'Impossible de retirer des points dans un trimestre fermé.',
            ]);
        }

        $this->verifierPeriodeDansAnnee($validated, $inscription);

        $sanctionAppliquee = SanctionAppliquee::create([
            'inscription_id' => $inscription->id,
            'sanction_id' => $sanction->id,
            'trimestre_id' => $trimestre?->id,
            'origine' => 'manuel',
            'date_application' => $validated['date_application'] ?? today()->toDateString(),
            'periode_debut' => $validated['periode_debut'] ?? null,
            'periode_fin' => $validated['periode_fin'] ?? null,
            'nombre_evenements' => 0,
            'motif' => $validated['motif'],
            'commentaire_interne' => $validated['commentaire_interne'] ?? null,
            'statut' => 'appliquee',
            'visible_parent' => $request->boolean('visible_parent'),
            'type_effet' => $sanction->type_effet,
            'valeur_effet' => $sanction->valeur_effet,
            'applique_par' => Auth::id(),
            'decision_par' => Auth::id(),
            'decision_le' => now(),
        ]);

        $notificationScolaireService->notifierSanctionAppliquee($sanctionAppliquee->fresh([
            'inscription.eleve.parents',
            'sanction',
            'trimestre',
        ]));

        return redirect()
            ->route('sanctions-appliquees.show', $sanctionAppliquee)
            ->with('success', 'Sanction manuelle appliquée avec succès.');
    }

    public function show(SanctionAppliquee $sanction_appliquee)
    {
        $sanction_appliquee->load([
            'inscription.eleve',
            'inscription.classe.anneeScolaire',
            'sanction',
            'trimestre',
            'appliquePar',
            'decisionPar',
        ]);
        $this->verifierAccesClasse((int) $sanction_appliquee->inscription->classe_id);

        return view('sanctions_appliquees.show', [
            'sanctionAppliquee' => $sanction_appliquee,
        ]);
    }

    public function appliquer(SanctionAppliquee $sanction_appliquee, NotificationScolaireService $notificationScolaireService)
    {
        $this->verifierGestionnaire();
        $this->verifierSanctionModifiable($sanction_appliquee);

        if ($sanction_appliquee->statut !== 'proposee') {
            return back()->withErrors([
                'sanction' => 'Seule une sanction proposée peut être appliquée.',
            ]);
        }

        if ($sanction_appliquee->type_effet === 'points_en_moins'
            && ! $sanction_appliquee->trimestre_id) {
            return back()->withErrors([
                'sanction' => 'Cette sanction doit être associée à un trimestre avant application.',
            ]);
        }

        $sanction_appliquee->update([
            'statut' => 'appliquee',
            'date_application' => $sanction_appliquee->date_application ?? today(),
            'applique_par' => Auth::id(),
            'decision_par' => Auth::id(),
            'decision_le' => now(),
        ]);

        $notificationScolaireService->notifierSanctionAppliquee($sanction_appliquee->fresh([
            'inscription.eleve.parents',
            'sanction',
            'trimestre',
        ]));

        return back()->with('success', 'Sanction appliquée.');
    }

    public function ignorer(SanctionAppliquee $sanction_appliquee)
    {
        $this->verifierGestionnaire();
        $this->verifierSanctionModifiable($sanction_appliquee);

        if ($sanction_appliquee->statut !== 'proposee') {
            return back()->withErrors([
                'sanction' => 'Seule une sanction proposée peut être ignorée.',
            ]);
        }

        $sanction_appliquee->update([
            'statut' => 'ignoree',
            'decision_par' => Auth::id(),
            'decision_le' => now(),
        ]);

        return back()->with('success', 'Proposition ignorée.');
    }

    public function annuler(SanctionAppliquee $sanction_appliquee)
    {
        $this->verifierGestionnaire();
        $this->verifierSanctionModifiable($sanction_appliquee);

        if ($sanction_appliquee->statut !== 'appliquee') {
            return back()->withErrors([
                'sanction' => 'Seule une sanction appliquée peut être annulée.',
            ]);
        }

        $sanction_appliquee->update([
            'statut' => 'annulee',
            'decision_par' => Auth::id(),
            'decision_le' => now(),
        ]);

        return back()->with('success', 'Sanction annulée.');
    }

    public function terminer(SanctionAppliquee $sanction_appliquee)
    {
        $this->verifierGestionnaire();
        $this->verifierSanctionModifiable($sanction_appliquee);

        if ($sanction_appliquee->statut !== 'appliquee') {
            return back()->withErrors([
                'sanction' => 'Seule une sanction appliquée peut être terminée.',
            ]);
        }

        if ($sanction_appliquee->type_effet === 'points_en_moins'
            && ! $sanction_appliquee->trimestre_id) {
            return back()->withErrors([
                'sanction' => 'Impossible de terminer cette sanction : le trimestre concerné est obligatoire pour les points en moins.',
            ]);
        }

        $sanction_appliquee->update([
            'statut' => 'terminee',
            'decision_par' => Auth::id(),
            'decision_le' => now(),
        ]);

        return back()->with('success', 'Sanction terminée.');
    }

    private function classesAccessibles($user, $anneeId)
    {
        $query = Classe::with('anneeScolaire')
            ->when($anneeId, fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->orderBy('niveau')
            ->orderBy('nom');

        if ($user->estEnseignant()) {
            $classeIds = ClasseMatiereUser::where('user_id', $user->id)
                ->whereIn('statut', ['actif', 'termine'])
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

    private function verifierAccesClasse(int $classeId): void
    {
        $user = Auth::user();

        if ($user->estGestionnaire()) {
            return;
        }

        $autorise = ClasseMatiereUser::where('user_id', $user->id)
            ->where('classe_id', $classeId)
            ->whereIn('statut', ['actif', 'termine'])
            ->exists();

        abort_unless($autorise, 403, 'Accès refusé.');
    }

    private function verifierGestionnaire(): void
    {
        abort_unless(Auth::user()->estGestionnaire(), 403, 'Accès refusé.');
    }

    private function verifierSanctionModifiable(SanctionAppliquee $sanctionAppliquee): void
    {
        $sanctionAppliquee->loadMissing([
            'inscription.anneeScolaire',
            'trimestre',
        ]);

        if ($sanctionAppliquee->inscription?->anneeScolaire?->estFermee()) {
            throw ValidationException::withMessages([
                'sanction' => 'Cette année scolaire est fermée : les sanctions sont consultables uniquement en historique.',
            ]);
        }

        if ($sanctionAppliquee->type_effet === 'points_en_moins'
            && $sanctionAppliquee->trimestre?->estFerme()) {
            throw ValidationException::withMessages([
                'sanction' => 'Impossible de modifier une sanction de points liée à un trimestre fermé.',
            ]);
        }
    }

    private function verifierPeriodeDansAnnee(array $validated, Inscription $inscription): void
    {
        $annee = $inscription->anneeScolaire;

        foreach (['date_application', 'periode_debut', 'periode_fin'] as $champ) {
            if (empty($validated[$champ])) {
                continue;
            }

            $date = Carbon::parse($validated[$champ])->startOfDay();

            if (($annee?->date_debut && $date->lt($annee->date_debut))
                || ($annee?->date_fin && $date->gt($annee->date_fin))) {
                throw ValidationException::withMessages([
                    $champ => 'Cette date doit appartenir à l’année scolaire de l’inscription.',
                ]);
            }
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
