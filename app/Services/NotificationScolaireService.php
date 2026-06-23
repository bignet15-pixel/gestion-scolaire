<?php

namespace App\Services;

use App\Models\AbsenceRetard;
use App\Models\DemandeReinscription;
use App\Models\Inscription;
use App\Models\JustificationAbsenceRetard;
use App\Models\Note;
use App\Models\NotificationUtilisateur;
use App\Models\PaiementDeclare;
use App\Models\SanctionAppliquee;
use App\Models\Trimestre;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationScolaireService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Informe les parents quand une note est disponible.
     */
    public function notifierNote(Note $note): void
    {
        $note->loadMissing([
            'inscription.eleve.parents',
            'evaluation.matiere',
            'evaluation.trimestre',
        ]);

        $inscription = $note->inscription;
        $evaluation = $note->evaluation;
        $eleve = $inscription?->eleve;
        $matiere = $evaluation?->matiere?->nom ?? 'une matière';

        if (! $inscription || ! $eleve || ! $evaluation) {
            return;
        }

        $valeur = number_format((float) $note->valeur, 2, ',', ' ');
        $bareme = number_format((float) $evaluation->bareme, 2, ',', ' ');
        $pourcentage = (float) $evaluation->bareme > 0
            ? ((float) $note->valeur / (float) $evaluation->bareme) * 100
            : null;

        $resume = $pourcentage !== null && $pourcentage < 50
            ? "Une note nécessitant votre attention a été enregistrée pour votre enfant en {$matiere}."
            : "Votre enfant a reçu une note en {$matiere}.";

        foreach ($this->parentsInscription($inscription) as $parent) {
            $this->notificationService->notifier(
                user: $parent,
                titre: 'Note enregistrée',
                message: "Votre enfant {$eleve->nomComplet()} a reçu {$valeur}/{$bareme} en {$matiere}. Appréciation : {$note->appreciation}.",
                type: 'note',
                lien: route('parent.eleves.show', $eleve, false),
                source: $note,
                resumeEmail: $resume,
                raisonConnexion: 'Connectez-vous pour consulter la note obtenue, l’appréciation et les détails de l’évaluation.',
                metadata: [
                    'eleve_id' => $eleve->id,
                    'inscription_id' => $inscription->id,
                    'evaluation_id' => $evaluation->id,
                    'matiere' => $matiere,
                    'trimestre_id' => $evaluation->trimestre_id,
                ],
                eviterDoublon: true,
            );
        }
    }

    /**
     * Informe les parents quand une absence ou un retard visible parent est enregistré.
     */
    public function notifierAbsenceRetard(AbsenceRetard $evenement): void
    {
        $evenement->loadMissing([
            'inscription.eleve.parents',
            'inscription.classe',
        ]);

        if (! $evenement->visible_parent || ! $evenement->inscription?->eleve) {
            return;
        }

        $inscription = $evenement->inscription;
        $eleve = $inscription->eleve;
        $type = $evenement->type === 'retard' ? 'retard' : 'absence';
        $typeLibelle = $evenement->type === 'retard' ? 'Retard' : 'Absence';
        $date = $evenement->date_debut?->format('d/m/Y') ?? 'date non précisée';

        $message = $type === 'retard'
            ? "Un retard a été enregistré pour votre enfant {$eleve->nomComplet()} le {$date}."
            : "Une absence a été enregistrée pour votre enfant {$eleve->nomComplet()} le {$date}.";

        if ($type === 'retard' && $evenement->duree_minutes) {
            $message .= " Durée : {$evenement->duree_minutes} minute(s).";
        }

        foreach ($this->parentsInscription($inscription) as $parent) {
            $this->notificationService->notifier(
                user: $parent,
                titre: "{$typeLibelle} enregistré",
                message: $message,
                type: $type,
                lien: route('parent.eleves.show', $eleve, false),
                source: $evenement,
                resumeEmail: $type === 'retard'
                    ? 'Un retard a été enregistré concernant votre enfant.'
                    : 'Une absence a été enregistrée concernant votre enfant.',
                raisonConnexion: $type === 'retard'
                    ? 'Connectez-vous pour consulter les détails du retard et soumettre une justification si nécessaire.'
                    : 'Connectez-vous pour consulter la date, la période concernée et soumettre une justification si nécessaire.',
                metadata: [
                    'eleve_id' => $eleve->id,
                    'inscription_id' => $inscription->id,
                    'date_debut' => optional($evenement->date_debut)->toDateString(),
                    'periode' => $evenement->periode,
                ],
                eviterDoublon: true,
            );
        }
    }

    /**
     * Informe le parent quand une déclaration de paiement est traitée.
     */
    public function notifierPaiementDeclareTraite(PaiementDeclare $paiementDeclare): void
    {
        $paiementDeclare->loadMissing([
            'inscription.eleve.parents',
            'parent',
            'paiement',
        ]);

        $parent = $paiementDeclare->parent;
        $inscription = $paiementDeclare->inscription;
        $eleve = $inscription?->eleve;

        if (! $parent || ! $inscription || ! $eleve) {
            return;
        }

        $estValide = $paiementDeclare->estValide();
        $montant = number_format((float) $paiementDeclare->montant, 0, ',', ' ');

        $this->notificationService->notifier(
            user: $parent,
            titre: $estValide ? 'Paiement validé' : 'Paiement à vérifier',
            message: $estValide
                ? "Votre déclaration de paiement de {$montant} FCFA pour {$eleve->nomComplet()} a été validée par l’administration."
                : "Votre déclaration de paiement pour {$eleve->nomComplet()} n’a pas pu être validée par l’administration. Motif : {$paiementDeclare->commentaire_validation}",
            type: 'paiement',
            lien: route('parent.paiements-declares.index', [], false),
            source: $paiementDeclare,
            resumeEmail: $estValide
                ? 'Votre déclaration de paiement a été validée par l’administration.'
                : 'Votre déclaration de paiement n’a pas pu être validée par l’administration.',
            raisonConnexion: $estValide
                ? 'Connectez-vous pour consulter les détails du paiement et télécharger le reçu si disponible.'
                : 'Connectez-vous pour consulter le motif du refus et effectuer une nouvelle déclaration si nécessaire.',
            metadata: [
                'eleve_id' => $eleve->id,
                'inscription_id' => $inscription->id,
                'paiement_id' => $paiementDeclare->paiement_id,
                'statut' => $paiementDeclare->statut,
            ],
            eviterDoublon: true,
        );
    }

    /**
     * Informe le parent quand une justification d'absence/retard est traitée.
     */
    public function notifierJustificationTraitee(JustificationAbsenceRetard $justification): void
    {
        $justification->loadMissing([
            'absenceRetard.inscription.eleve.parents',
            'parent',
        ]);

        $parent = $justification->parent;
        $evenement = $justification->absenceRetard;
        $inscription = $evenement?->inscription;
        $eleve = $inscription?->eleve;

        if (! $parent || ! $evenement || ! $inscription || ! $eleve) {
            return;
        }

        $decision = $justification->estAcceptee() ? 'acceptée' : 'refusée';

        $this->notificationService->notifier(
            user: $parent,
            titre: 'Justification traitée',
            message: "Votre justification concernant {$evenement->libelleType()} de {$eleve->nomComplet()} a été {$decision}.",
            type: 'justification',
            lien: route('parent.eleves.show', $eleve, false),
            source: $justification,
            resumeEmail: 'Une justification d’absence ou de retard que vous avez soumise a été traitée par l’administration.',
            raisonConnexion: 'Connectez-vous pour consulter la décision et les détails associés.',
            metadata: [
                'eleve_id' => $eleve->id,
                'inscription_id' => $inscription->id,
                'absence_retard_id' => $evenement->id,
                'statut' => $justification->statut,
            ],
            eviterDoublon: true,
        );
    }

    /**
     * Informe le parent quand une demande de réinscription est traitée.
     */
    public function notifierDemandeReinscriptionTraitee(DemandeReinscription $demande): void
    {
        $demande->loadMissing([
            'eleve.parents',
            'parent',
            'classeDemandee',
            'nouvelleAnneeScolaire',
        ]);

        $parent = $demande->parent;
        $eleve = $demande->eleve;

        if (! $parent || ! $eleve) {
            return;
        }

        $decision = $demande->estValidee() ? 'validée' : 'refusée';

        $this->notificationService->notifier(
            user: $parent,
            titre: 'Demande de réinscription traitée',
            message: "La demande de réinscription de {$eleve->nomComplet()} a été {$decision}.",
            type: 'reinscription',
            lien: route('parent.eleves.show', $eleve, false),
            source: $demande,
            resumeEmail: 'La demande de réinscription concernant votre enfant a été traitée par l’administration.',
            raisonConnexion: 'Connectez-vous pour consulter la décision et les informations liées à la nouvelle année scolaire.',
            metadata: [
                'eleve_id' => $eleve->id,
                'classe_demandee_id' => $demande->classe_demandee_id,
                'nouvelle_annee_scolaire_id' => $demande->nouvelle_annee_scolaire_id,
                'statut' => $demande->statut,
            ],
            eviterDoublon: true,
        );
    }

    /**
     * Informe les parents quand une sanction visible parent est appliquée.
     */
    public function notifierSanctionAppliquee(SanctionAppliquee $sanctionAppliquee): void
    {
        $sanctionAppliquee->loadMissing([
            'inscription.eleve.parents',
            'sanction',
            'trimestre',
        ]);

        if (! $sanctionAppliquee->visible_parent || $sanctionAppliquee->statut !== 'appliquee') {
            return;
        }

        $inscription = $sanctionAppliquee->inscription;
        $eleve = $inscription?->eleve;
        $sanction = $sanctionAppliquee->sanction;

        if (! $inscription || ! $eleve || ! $sanction) {
            return;
        }

        foreach ($this->parentsInscription($inscription) as $parent) {
            $this->notificationService->notifier(
                user: $parent,
                titre: 'Information disciplinaire',
                message: "Une sanction a été appliquée à votre enfant {$eleve->nomComplet()} : {$sanction->nom}. Motif : {$sanctionAppliquee->motif}",
                type: 'sanction',
                lien: route('parent.eleves.show', $eleve, false),
                source: $sanctionAppliquee,
                resumeEmail: 'Une information disciplinaire concernant votre enfant est disponible dans votre espace parent.',
                raisonConnexion: 'Connectez-vous pour consulter le motif, la mesure appliquée et les démarches éventuelles à effectuer.',
                metadata: [
                    'eleve_id' => $eleve->id,
                    'inscription_id' => $inscription->id,
                    'sanction_id' => $sanction->id,
                    'trimestre_id' => $sanctionAppliquee->trimestre_id,
                ],
                eviterDoublon: true,
            );
        }
    }

    /**
     * Informe les parents quand les résultats d'un trimestre fermé sont disponibles.
     */
    public function notifierResultatsTrimestreDisponibles(Trimestre $trimestre): int
    {
        $trimestre->loadMissing('anneeScolaire');

        $inscriptions = Inscription::with(['eleve.parents', 'classe'])
            ->where('annee_scolaire_id', $trimestre->annee_scolaire_id)
            ->where('statut', 'actif')
            ->get();

        $nombre = 0;

        foreach ($inscriptions as $inscription) {
            if (! $inscription->eleve) {
                continue;
            }

            foreach ($this->parentsInscription($inscription) as $parent) {
                if ($this->notificationResultatsExiste($parent, $trimestre, $inscription)) {
                    continue;
                }

                $this->notificationService->notifier(
                    user: $parent,
                    titre: 'Résultats du trimestre disponibles',
                    message: "Les résultats du {$trimestre->nom} de {$inscription->eleve->nomComplet()} sont disponibles. Vous pouvez consulter les moyennes, le classement et télécharger le bulletin.",
                    type: 'resultats',
                    lien: route('parent.bulletins.trimestriel', [$inscription, $trimestre], false),
                    source: $trimestre,
                    resumeEmail: "Les résultats du {$trimestre->nom} sont disponibles pour votre enfant.",
                    raisonConnexion: 'Connectez-vous pour voir les résultats et télécharger le bulletin.',
                    metadata: [
                        'eleve_id' => $inscription->eleve_id,
                        'inscription_id' => $inscription->id,
                        'classe_id' => $inscription->classe_id,
                        'trimestre_id' => $trimestre->id,
                    ],
                    eviterDoublon: false,
                );

                $nombre++;
            }
        }

        return $nombre;
    }

    private function parentsInscription(Inscription $inscription): Collection
    {
        $inscription->loadMissing('eleve.parents');

        return $inscription->eleve?->parents
            ? $inscription->eleve->parents->filter(fn (User $parent) => $parent->estParent())->values()
            : collect();
    }

    private function notificationResultatsExiste(User $parent, Trimestre $trimestre, Inscription $inscription): bool
    {
        return NotificationUtilisateur::query()
            ->where('user_id', $parent->id)
            ->where('type', 'resultats')
            ->where('source_type', $trimestre->getMorphClass())
            ->where('source_id', $trimestre->id)
            ->where('metadata->inscription_id', $inscription->id)
            ->exists();
    }
}
