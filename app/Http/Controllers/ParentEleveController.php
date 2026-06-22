<?php

namespace App\Http\Controllers;

use App\Models\AbsenceRetard;
use App\Models\Eleve;
use App\Models\SanctionAppliquee;

class ParentEleveController extends Controller
{
    /**
     * Affiche la fiche d'un enfant pour le parent connecté.
     */
    public function show(Eleve $eleve)
    {
        $parent = auth()->user();

        $autorise = $parent->enfants()
            ->where('eleves.id', $eleve->id)
            ->exists();

        abort_unless($autorise, 403, 'Vous ne pouvez consulter que vos enfants.');

        $eleve->load([
            'inscriptions.classe.anneeScolaire',
            'inscriptions.paiements',
            'inscriptions.notes.evaluation.matiere',
            'inscriptions.notes.evaluation.trimestre',
        ]);

        $inscriptionIds = $eleve->inscriptions->pluck('id');

        $absencesRetards = AbsenceRetard::with(['inscription.classe'])
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('visible_parent', true)
            ->orderByDesc('date_debut')
            ->get();

        $sanctions = SanctionAppliquee::with(['sanction', 'trimestre', 'inscription.classe'])
            ->whereIn('inscription_id', $inscriptionIds)
            ->where('visible_parent', true)
            ->orderByDesc('created_at')
            ->get();

        return view('parent.eleves.show', compact(
            'eleve',
            'absencesRetards',
            'sanctions'
        ));
    }
}
