<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\Classe;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GestionnaireAnnonceController extends Controller
{
    public function index(Request $request)
    {
        $annonces = Annonce::with(['auteur', 'classe'])
            ->withCount('notifications')
            ->when($request->filled('statut'), function ($query) use ($request) {
                if ($request->input('statut') === 'publiee') {
                    $query->where('est_publiee', true);
                }

                if ($request->input('statut') === 'brouillon') {
                    $query->where('est_publiee', false);
                }
            })
            ->when($request->filled('cible'), fn ($query) => $query->where('cible', $request->input('cible')))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('annonces.index', [
            'annonces' => $annonces,
            'cibles' => Annonce::CIBLES,
        ]);
    }

    public function create()
    {
        return view('annonces.create', $this->formData());
    }

    public function store(Request $request, NotificationService $notificationService)
    {
        $validated = $this->validateAnnonce($request);
        $publier = $request->input('action') === 'publier';

        $validated['publie_par'] = auth()->id();
        $validated['est_publiee'] = $publier;
        $validated['date_publication'] = $publier ? now() : null;

        $annonce = Annonce::create($validated);

        if ($publier) {
            $nombre = $notificationService->publierAnnonce($annonce);

            return redirect()
                ->route('annonces.show', $annonce)
                ->with('success', "Annonce publiée avec succès. {$nombre} notification(s) préparée(s) et email(s) placé(s) dans la file d’attente.");
        }

        return redirect()
            ->route('annonces.show', $annonce)
            ->with('success', 'Annonce enregistrée en brouillon.');
    }

    public function show(Annonce $annonce)
    {
        $annonce->load(['auteur', 'classe']);

        $stats = [
            'destinataires' => $annonce->notifications()->count(),
            'emails_envoyes' => $annonce->notifications()->where('email_statut', 'sent')->count(),
            'emails_en_file' => $annonce->notifications()->where('email_statut', 'queued')->count(),
            'emails_echoues' => $annonce->notifications()->where('email_statut', 'failed')->count(),
            'lues' => $annonce->notifications()->where('lue', true)->count(),
        ];

        return view('annonces.show', compact('annonce', 'stats'));
    }

    public function edit(Annonce $annonce)
    {
        return view('annonces.edit', array_merge($this->formData(), compact('annonce')));
    }

    public function update(Request $request, Annonce $annonce, NotificationService $notificationService)
    {
        $validated = $this->validateAnnonce($request, $annonce);
        $publier = $request->input('action') === 'publier' && ! $annonce->est_publiee;

        if ($publier) {
            $validated['est_publiee'] = true;
            $validated['date_publication'] = now();
        }

        $annonce->update($validated);

        if ($publier) {
            $nombre = $notificationService->publierAnnonce($annonce->fresh(['classe']));

            return redirect()
                ->route('annonces.show', $annonce)
                ->with('success', "Annonce modifiée et publiée. {$nombre} notification(s) préparée(s) et email(s) placé(s) dans la file d’attente.");
        }

        return redirect()
            ->route('annonces.show', $annonce)
            ->with('success', 'Annonce modifiée avec succès.');
    }

    public function destroy(Annonce $annonce)
    {
        $annonce->update(['is_deleted' => true]);
        $annonce->delete();

        return redirect()
            ->route('annonces.index')
            ->with('success', 'Annonce supprimée avec succès.');
    }

    public function publier(Annonce $annonce, NotificationService $notificationService)
    {
        if ($annonce->est_publiee) {
            return redirect()
                ->route('annonces.show', $annonce)
                ->with('success', 'Cette annonce est déjà publiée.');
        }

        $annonce->update([
            'est_publiee' => true,
            'date_publication' => now(),
        ]);

        $nombre = $notificationService->publierAnnonce($annonce->fresh(['classe']));

        return redirect()
            ->route('annonces.show', $annonce)
            ->with('success', "Annonce publiée avec succès. {$nombre} notification(s) préparée(s) et email(s) placé(s) dans la file d’attente.");
    }

    private function validateAnnonce(Request $request, ?Annonce $annonce = null): array
    {
        return $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'contenu' => ['required', 'string'],
            'type' => ['required', Rule::in(array_keys(Annonce::TYPES))],
            'priorite' => ['required', Rule::in(array_keys(Annonce::PRIORITES))],
            'cible' => ['required', Rule::in(array_keys(Annonce::CIBLES))],
            'classe_id' => [
                'nullable',
                'required_if:cible,classe',
                'exists:classes,id',
            ],
            'date_expiration' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
    }

    private function formData(): array
    {
        return [
            'types' => Annonce::TYPES,
            'priorites' => Annonce::PRIORITES,
            'cibles' => Annonce::CIBLES,
            'classes' => Classe::with('anneeScolaire')
                ->orderByDesc('annee_scolaire_id')
                ->orderBy('niveau')
                ->orderBy('nom')
                ->get(),
        ];
    }
}
