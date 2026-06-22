<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\DemandeReinscription;
use App\Models\Inscription;
use RuntimeException;

class ParentReinscriptionService
{
    private const NIVEAUX = ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];

    public function __construct(
        private BulletinService $bulletinService
    ) {}

    /**
     * Prépare l'option de réinscription autorisée par le système.
     *
     * Le parent ne choisit pas librement : la décision annuelle contrôle
     * si l'élève peut passer ou doit redoubler.
     */
    public function optionPourInscription(Inscription $inscription): array
    {
        $inscription->loadMissing([
            'eleve',
            'classe',
            'anneeScolaire',
            'paiements',
        ]);

        if (! $inscription->eleve || ! $inscription->classe || ! $inscription->anneeScolaire) {
            return $this->indisponible('Inscription incomplète.');
        }

        if (! $inscription->estSoldee()) {
            return $this->indisponible('Les frais scolaires de l’année actuelle doivent être soldés avant une demande de réinscription.');
        }

        try {
            $bulletinAnnuel = $this->bulletinService->bulletinAnnuel($inscription);
        } catch (RuntimeException $exception) {
            return $this->indisponible($exception->getMessage());
        }

        $nouvelleAnnee = $this->anneeSuivante($inscription);

        if (! $nouvelleAnnee) {
            return $this->indisponible('Aucune année scolaire suivante n’est encore ouverte.');
        }

        $dejaInscrit = Inscription::query()
            ->where('eleve_id', $inscription->eleve_id)
            ->where('annee_scolaire_id', $nouvelleAnnee->id)
            ->exists();

        if ($dejaInscrit) {
            return $this->indisponible('Cet élève possède déjà une inscription pour l’année scolaire suivante.');
        }

        $demandeEnAttente = DemandeReinscription::query()
            ->with(['classeDemandee', 'nouvelleAnneeScolaire'])
            ->where('eleve_id', $inscription->eleve_id)
            ->where('nouvelle_annee_scolaire_id', $nouvelleAnnee->id)
            ->where('statut', DemandeReinscription::STATUT_EN_ATTENTE)
            ->latest()
            ->first();

        if ($demandeEnAttente) {
            return [
                'possible' => false,
                'raison' => 'Une demande de réinscription est déjà en attente pour cet enfant.',
                'demande_en_attente' => $demandeEnAttente,
                'bulletin_annuel' => $bulletinAnnuel,
                'nouvelle_annee' => $nouvelleAnnee,
            ];
        }

        $niveauDemande = $this->niveauDemande(
            $inscription->classe->niveau,
            $bulletinAnnuel['decision'] ?? null
        );

        if (! $niveauDemande) {
            return $this->indisponible(
                'Aucune classe supérieure disponible dans l’établissement.',
                $bulletinAnnuel,
                $nouvelleAnnee
            );
        }

        $classesDisponibles = Classe::query()
            ->where('annee_scolaire_id', $nouvelleAnnee->id)
            ->where('niveau', $niveauDemande)
            ->orderBy('nom')
            ->get();

        if ($classesDisponibles->isEmpty()) {
            return $this->indisponible(
                'Aucune classe de niveau '.$niveauDemande.' n’est disponible pour l’année scolaire suivante.',
                $bulletinAnnuel,
                $nouvelleAnnee
            );
        }

        $typeDemande = ($bulletinAnnuel['decision'] ?? null) === 'Redouble'
            ? DemandeReinscription::TYPE_REDOUBLEMENT
            : DemandeReinscription::TYPE_PASSAGE_SUPERIEUR;

        $decisionSysteme = $typeDemande === DemandeReinscription::TYPE_REDOUBLEMENT
            ? DemandeReinscription::DECISION_REDOUBLEMENT_OBLIGATOIRE
            : DemandeReinscription::DECISION_PASSAGE_AUTORISE;

        return [
            'possible' => true,
            'raison' => null,
            'bulletin_annuel' => $bulletinAnnuel,
            'nouvelle_annee' => $nouvelleAnnee,
            'niveau_demande' => $niveauDemande,
            'classes_disponibles' => $classesDisponibles,
            'type_demande' => $typeDemande,
            'decision_systeme' => $decisionSysteme,
            'demande_en_attente' => null,
        ];
    }

    public function verifierClasseAutorisee(Inscription $inscription, Classe $classe): array
    {
        $option = $this->optionPourInscription($inscription);

        if (! ($option['possible'] ?? false)) {
            throw new RuntimeException($option['raison'] ?? 'Réinscription non disponible.');
        }

        $autorisee = $option['classes_disponibles']
            ->contains(fn (Classe $classeDisponible) => (int) $classeDisponible->id === (int) $classe->id);

        if (! $autorisee) {
            throw new RuntimeException('La classe demandée ne respecte pas la décision du système.');
        }

        return $option;
    }

    private function anneeSuivante(Inscription $inscription): ?AnneeScolaire
    {
        if (! $inscription->anneeScolaire?->date_debut) {
            return AnneeScolaire::query()
                ->where('id', '!=', $inscription->annee_scolaire_id)
                ->orderBy('date_debut')
                ->first();
        }

        return AnneeScolaire::query()
            ->whereDate('date_debut', '>', $inscription->anneeScolaire->date_debut->toDateString())
            ->orderBy('date_debut')
            ->first();
    }

    private function niveauDemande(string $niveauActuel, ?string $decision): ?string
    {
        if ($decision === 'Redouble') {
            return $niveauActuel;
        }

        $position = array_search($niveauActuel, self::NIVEAUX, true);

        if ($position === false) {
            return null;
        }

        return self::NIVEAUX[$position + 1] ?? null;
    }

    private function indisponible(
        string $raison,
        ?array $bulletinAnnuel = null,
        ?AnneeScolaire $nouvelleAnnee = null
    ): array {
        return [
            'possible' => false,
            'raison' => $raison,
            'bulletin_annuel' => $bulletinAnnuel,
            'nouvelle_annee' => $nouvelleAnnee,
            'classes_disponibles' => collect(),
            'demande_en_attente' => null,
        ];
    }
}
