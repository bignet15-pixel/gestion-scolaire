<?php

namespace App\Http\Controllers;

use App\Models\AbsenceRetard;
use App\Models\JustificationAbsenceRetard;
use App\Services\ParentAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ParentJustificationAbsenceRetardController extends Controller
{
    public function __construct(
        private ParentAccessService $parentAccessService
    ) {}


    public function create(AbsenceRetard $absence_retard)
    {
        $parent = auth()->user();
        $absence_retard->loadMissing([
            'inscription.eleve',
            'inscription.classe.anneeScolaire',
            'justificationParentale',
        ]);

        $this->parentAccessService->assertCanAccessAbsenceRetard($parent, $absence_retard);

        if ($absence_retard->statut === 'justifiee') {
            return redirect()
                ->route('parent.eleves.show', $absence_retard->inscription->eleve)
                ->withErrors(['justification' => 'Cet événement est déjà marqué comme justifié.']);
        }

        if ($absence_retard->justificationParentale) {
            return redirect()
                ->route('parent.eleves.show', $absence_retard->inscription->eleve)
                ->withErrors(['justification' => 'Une demande de justification existe déjà pour cet événement.']);
        }

        return view('parent.justifications.create', compact('absence_retard'));
    }

    /**
     * Le parent soumet une demande de justification.
     * L'absence officielle ne devient pas justifiée ici : elle attend la validation.
     */
    public function store(Request $request, AbsenceRetard $absence_retard)
    {
        $parent = auth()->user();
        $absence_retard->loadMissing('inscription.eleve');
        $this->parentAccessService->assertCanAccessAbsenceRetard($parent, $absence_retard);

        if ($absence_retard->statut === 'justifiee') {
            return back()->withErrors([
                'justification' => 'Cet événement est déjà marqué comme justifié.',
            ]);
        }

        $existe = JustificationAbsenceRetard::query()
            ->where('absence_retard_id', $absence_retard->id)
            ->exists();

        if ($existe) {
            return back()->withErrors([
                'justification' => 'Une demande de justification existe déjà pour cet événement.',
            ]);
        }

        $validated = $request->validate([
            'motif' => ['required', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:3000'],
            'piece_jointe' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('piece_jointe')) {
            $validated['piece_jointe'] = $request
                ->file('piece_jointe')
                ->store('parent/justifications', 'public');
        }

        JustificationAbsenceRetard::create([
            'absence_retard_id' => $absence_retard->id,
            'parent_id' => $parent->id,
            'motif' => $validated['motif'],
            'message' => $validated['message'] ?? null,
            'piece_jointe' => $validated['piece_jointe'] ?? null,
            'statut' => JustificationAbsenceRetard::STATUT_EN_ATTENTE,
        ]);

        return redirect()
            ->route('parent.eleves.show', $absence_retard->inscription->eleve)
            ->with('success', 'Justification envoyée. Elle attend la validation de l’école.');
    }

    public function piece(JustificationAbsenceRetard $justification)
    {
        $parent = auth()->user();
        $justification->loadMissing('absenceRetard.inscription');

        abort_unless(
            $justification->piece_jointe
                && $this->parentAccessService->canAccessAbsenceRetard($parent, $justification->absenceRetard),
            404
        );

        return Storage::disk('public')->response($justification->piece_jointe);
    }
}
