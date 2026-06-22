<?php

namespace App\Services;

use App\Models\AbsenceRetard;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\User;

class ParentAccessService
{
    public function canAccessEleve(User $parent, Eleve $eleve): bool
    {
        return $parent->enfants()
            ->where('eleves.id', $eleve->id)
            ->exists();
    }

    public function assertCanAccessEleve(User $parent, Eleve $eleve): void
    {
        abort_unless(
            $this->canAccessEleve($parent, $eleve),
            403,
            'Vous ne pouvez consulter que vos enfants.'
        );
    }

    public function canAccessInscription(User $parent, Inscription $inscription): bool
    {
        if (! $inscription->eleve_id) {
            return false;
        }

        return $parent->enfants()
            ->where('eleves.id', $inscription->eleve_id)
            ->exists();
    }

    public function assertCanAccessInscription(User $parent, Inscription $inscription): void
    {
        abort_unless(
            $this->canAccessInscription($parent, $inscription),
            403,
            'Vous ne pouvez agir que sur les inscriptions de vos enfants.'
        );
    }

    public function canAccessAbsenceRetard(User $parent, AbsenceRetard $absenceRetard): bool
    {
        $absenceRetard->loadMissing('inscription');

        if (! $absenceRetard->inscription || ! $absenceRetard->visible_parent) {
            return false;
        }

        return $this->canAccessInscription($parent, $absenceRetard->inscription);
    }

    public function assertCanAccessAbsenceRetard(User $parent, AbsenceRetard $absenceRetard): void
    {
        abort_unless(
            $this->canAccessAbsenceRetard($parent, $absenceRetard),
            403,
            'Vous ne pouvez justifier que les absences ou retards visibles de vos enfants.'
        );
    }
}
