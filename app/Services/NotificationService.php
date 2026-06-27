<?php

namespace App\Services;

use App\Jobs\SendAnnonceDetailEmail;
use App\Jobs\SendNotificationAlertEmail;
use App\Models\Annonce;
use App\Models\Classe;
use App\Models\NotificationUtilisateur;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Crée une notification interne et envoie automatiquement l'email d'alerte.
     */
    public function notifier(
        User $user,
        string $titre,
        string $message,
        string $type = 'information',
        ?string $lien = null,
        ?Model $source = null,
        ?string $resumeEmail = null,
        ?string $raisonConnexion = null,
        array $metadata = [],
        bool $eviterDoublon = false,
    ): NotificationUtilisateur {
        if ($eviterDoublon && $source !== null) {
            $existante = NotificationUtilisateur::where('user_id', $user->id)
                ->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->getKey())
                ->first();

            if ($existante) {
                return $existante;
            }
        }

        $notification = NotificationUtilisateur::create([
            'user_id' => $user->id,
            'titre' => $titre,
            'message' => $message,
            'type' => $type,
            'lien' => $lien,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'email_mode' => 'alerte',
            'email_resume' => $resumeEmail ?: $this->resumeEmailParDefaut($type),
            'email_raison_connexion' => $raisonConnexion ?: $this->raisonConnexionParDefaut($type),
            'metadata' => $metadata ?: null,
        ]);

        $this->envoyerEmailNotification($notification);

        return $notification;
    }

    /**
     * Publie une annonce : crée les notifications des destinataires et envoie un email détaillé.
     */
    public function publierAnnonce(Annonce $annonce): int
    {
        $destinataires = $this->destinatairesAnnonce($annonce);
        $nombre = 0;

        foreach ($destinataires as $destinataire) {
            $notification = $this->notifierAnnonce($destinataire, $annonce);

            if ($notification->wasRecentlyCreated || $notification->email_statut !== 'sent') {
                $nombre++;
            }
        }

        return $nombre;
    }

    /**
     * Crée la notification interne associée à une annonce et envoie le mail complet.
     */
    public function notifierAnnonce(User $user, Annonce $annonce): NotificationUtilisateur
    {
        $notification = NotificationUtilisateur::where('user_id', $user->id)
            ->where('source_type', $annonce->getMorphClass())
            ->where('source_id', $annonce->id)
            ->first();

        if (! $notification) {
            $notification = NotificationUtilisateur::create([
                'user_id' => $user->id,
                'titre' => $annonce->titre,
                'message' => $annonce->contenu,
                'type' => 'annonce',
                'lien' => route('annonces-ecole.show', $annonce, false),
                'source_type' => $annonce->getMorphClass(),
                'source_id' => $annonce->id,
                'email_mode' => 'detail',
                'email_resume' => $annonce->contenu,
                'email_raison_connexion' => 'Cette annonce contient une information officielle de l’école.',
                'metadata' => [
                    'annonce_type' => $annonce->type,
                    'priorite' => $annonce->priorite,
                    'cible' => $annonce->cible,
                    'classe_id' => $annonce->classe_id,
                ],
            ]);
        }

        if (! in_array($notification->email_statut, ['sent', 'queued'], true)) {
            $this->envoyerEmailAnnonce($notification, $annonce);
        }

        return $notification;
    }

    /**
     * Retourne les utilisateurs concernés par une annonce.
     */
    public function destinatairesAnnonce(Annonce $annonce): Collection
    {
        return match ($annonce->cible) {
            'tous' => User::whereIn('role', ['parent', 'enseignant'])
                ->orderBy('nom')
                ->get(),
            'enseignants' => User::where('role', 'enseignant')
                ->orderBy('nom')
                ->get(),
            'classe' => $annonce->classe_id
                ? $this->destinatairesClasse($annonce->classe)
                : collect(),
            default => User::where('role', 'parent')
                ->orderBy('nom')
                ->get(),
        };
    }

    /**
     * Retourne les parents et enseignants liés à une classe précise.
     */
    private function destinatairesClasse(?Classe $classe): Collection
    {
        if (! $classe) {
            return collect();
        }

        $parentIds = User::where('role', 'parent')
            ->whereHas('enfants.inscriptions', function ($query) use ($classe) {
                $query->where('classe_id', $classe->id);
            })
            ->pluck('id');

        $enseignantIds = collect([$classe->enseignant_principal_id])
            ->merge($classe->affectations()->pluck('user_id'))
            ->filter();

        return User::whereIn('id', $parentIds->merge($enseignantIds)->unique()->values())
            ->orderBy('role')
            ->orderBy('nom')
            ->get();
    }

    private function envoyerEmailNotification(NotificationUtilisateur $notification): void
    {
        if (! $notification->user?->email) {
            $notification->update([
                'email_statut' => 'failed',
                'email_erreur' => 'Aucune adresse email disponible pour le destinataire.',
            ]);

            return;
        }

        $notification->update([
            'email_statut' => 'queued',
            'email_erreur' => null,
        ]);

        SendNotificationAlertEmail::dispatch($notification->id)
            ->afterCommit()
            ->onQueue('emails');
    }

    private function envoyerEmailAnnonce(NotificationUtilisateur $notification, Annonce $annonce): void
    {
        if (! $notification->user?->email) {
            $notification->update([
                'email_statut' => 'failed',
                'email_erreur' => 'Aucune adresse email disponible pour le destinataire.',
            ]);

            return;
        }

        $notification->update([
            'email_statut' => 'queued',
            'email_erreur' => null,
        ]);

        SendAnnonceDetailEmail::dispatch($notification->id, $annonce->id)
            ->afterCommit()
            ->onQueue('emails');
    }

    private function resumeEmailParDefaut(string $type): string
    {
        return match ($type) {
            'note' => 'Votre enfant a reçu une note dans une matière.',
            'absence' => 'Une absence a été enregistrée concernant votre enfant.',
            'retard' => 'Un retard a été enregistré concernant votre enfant.',
            'sanction' => 'Une information disciplinaire concernant votre enfant est disponible.',
            'paiement' => 'Une information concernant un paiement est disponible.',
            'justification' => 'Une justification d’absence ou de retard a été traitée.',
            'reinscription' => 'Une demande de réinscription a été traitée.',
            'resultats' => 'Les résultats du trimestre concerné sont disponibles.',
            default => 'Une information vous concernant est disponible dans votre espace.',
        };
    }

    private function raisonConnexionParDefaut(string $type): string
    {
        return match ($type) {
            'note' => 'Connectez-vous pour consulter la note, l’appréciation et les détails de l’évaluation.',
            'absence' => 'Connectez-vous pour consulter la date, la période concernée et soumettre une justification si nécessaire.',
            'retard' => 'Connectez-vous pour consulter les détails du retard et soumettre une justification si nécessaire.',
            'sanction' => 'Connectez-vous pour consulter le motif, la mesure appliquée et les démarches éventuelles à effectuer.',
            'paiement' => 'Connectez-vous pour consulter les détails du paiement et télécharger le reçu si disponible.',
            'justification' => 'Connectez-vous pour consulter la décision et les détails associés.',
            'reinscription' => 'Connectez-vous pour consulter la décision et les informations liées à la nouvelle année scolaire.',
            'resultats' => 'Connectez-vous pour voir les résultats et télécharger le bulletin.',
            default => 'Connectez-vous à votre espace pour consulter les détails.',
        };
    }
}
