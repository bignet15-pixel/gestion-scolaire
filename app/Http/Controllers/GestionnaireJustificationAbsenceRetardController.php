<?php

namespace App\Http\Controllers;

use App\Models\JustificationAbsenceRetard;
use App\Services\Assiduite\SanctionDetectionService;
use App\Services\NotificationScolaireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GestionnaireJustificationAbsenceRetardController extends Controller
{
    public function index(Request $request)
    {
        $selectedStatut = $request->input('statut');
        $search = trim((string) $request->input('q', ''));

        $justifications = JustificationAbsenceRetard::with([
                'absenceRetard.inscription.eleve',
                'absenceRetard.inscription.classe.anneeScolaire',
                'parent',
                'traitePar',
            ])
            ->when($selectedStatut, fn ($query) => $query->where('statut', $selectedStatut))
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('absenceRetard.inscription.eleve', function ($q) use ($search) {
                    $q->where('matricule', 'like', '%'.$search.'%')
                        ->orWhere('nom', 'like', '%'.$search.'%')
                        ->orWhere('prenom', 'like', '%'.$search.'%')
                        ->orWhereRaw("CONCAT(nom, ' ', prenom) LIKE ?", ['%'.$search.'%'])
                        ->orWhereRaw("CONCAT(prenom, ' ', nom) LIKE ?", ['%'.$search.'%']);
                });
            })
            ->orderByRaw("CASE statut WHEN 'en_attente' THEN 1 WHEN 'acceptee' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        return view('gestionnaire.justifications.index', compact(
            'justifications',
            'selectedStatut',
            'search'
        ));
    }


    public function show(JustificationAbsenceRetard $justification)
    {
        $justification->loadMissing([
            'absenceRetard.inscription.eleve',
            'absenceRetard.inscription.classe.anneeScolaire',
            'parent',
            'traitePar',
        ]);

        return view('gestionnaire.justifications.show', compact('justification'));
    }

    public function accepter(Request $request, JustificationAbsenceRetard $justification, SanctionDetectionService $detectionService, NotificationScolaireService $notificationScolaireService)
    {
        $validated = $request->validate([
            'commentaire_traitement' => ['nullable', 'string', 'max:3000'],
        ]);

        $justification->loadMissing('absenceRetard');

        abort_unless($justification->estEnAttente(), 422, 'Cette demande est déjà traitée.');

        $justification->update([
            'statut' => JustificationAbsenceRetard::STATUT_ACCEPTEE,
            'traite_par' => auth()->id(),
            'traite_le' => now(),
            'commentaire_traitement' => $validated['commentaire_traitement'] ?? null,
        ]);

        $justification->absenceRetard->update([
            'statut' => 'justifiee',
            'justification' => trim($justification->motif."\n".($justification->message ?? '')),
            'piece_justificative' => $justification->piece_jointe,
            'statut_mis_a_jour_par' => auth()->id(),
            'statut_mis_a_jour_le' => now(),
        ]);

        $detectionService->detecter($justification->absenceRetard->fresh());
        $notificationScolaireService->notifierJustificationTraitee($justification->fresh([
            'absenceRetard.inscription.eleve.parents',
            'parent',
        ]));

        return back()->with('success', 'Justification acceptée. L’absence ou le retard est maintenant justifié.');
    }

    public function refuser(Request $request, JustificationAbsenceRetard $justification, SanctionDetectionService $detectionService, NotificationScolaireService $notificationScolaireService)
    {
        $validated = $request->validate([
            'commentaire_traitement' => ['required', 'string', 'max:3000'],
        ]);

        $justification->loadMissing('absenceRetard');

        abort_unless($justification->estEnAttente(), 422, 'Cette demande est déjà traitée.');

        $justification->update([
            'statut' => JustificationAbsenceRetard::STATUT_REFUSEE,
            'traite_par' => auth()->id(),
            'traite_le' => now(),
            'commentaire_traitement' => $validated['commentaire_traitement'],
        ]);

        $justification->absenceRetard->update([
            'statut' => 'refusee',
            'statut_mis_a_jour_par' => auth()->id(),
            'statut_mis_a_jour_le' => now(),
        ]);

        $detectionService->detecter($justification->absenceRetard->fresh());
        $notificationScolaireService->notifierJustificationTraitee($justification->fresh([
            'absenceRetard.inscription.eleve.parents',
            'parent',
        ]));

        return back()->with('success', 'Justification refusée.');
    }

    public function piece(JustificationAbsenceRetard $justification)
    {
        abort_unless($justification->piece_jointe, 404);

        return Storage::disk('public')->response($justification->piece_jointe);
    }
}
