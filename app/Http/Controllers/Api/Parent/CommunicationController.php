<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Models\NotificationUtilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunicationController extends Controller
{
    public function annonces(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', Rule::in(array_keys(Annonce::TYPES))],
            'priorite' => ['nullable', 'string', Rule::in(array_keys(Annonce::PRIORITES))],
        ]);

        $notifications = $request->user()
            ->notificationsUtilisateur()
            ->where('type', 'annonce')
            ->where('source_type', Annonce::class)
            ->get()
            ->keyBy('source_id');

        $annonces = Annonce::with(['auteur', 'classe'])
            ->whereIn('id', $notifications->keys())
            ->where('est_publiee', true)
            ->where(function ($query) {
                $query->whereNull('date_expiration')
                    ->orWhere('date_expiration', '>=', now());
            })
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($validated['priorite'] ?? null, fn ($query, $priorite) => $query->where('priorite', $priorite))
            ->latest('date_publication')
            ->get()
            ->map(fn (Annonce $annonce) => $this->formatAnnonce($annonce, $notifications->get($annonce->id)));

        return $this->success('Annonces récupérées avec succès.', [
            'total' => $annonces->count(),
            'annonces' => $annonces,
        ]);
    }

    public function annonce(Request $request, Annonce $annonce): JsonResponse
    {
        $notification = $request->user()
            ->notificationsUtilisateur()
            ->where('type', 'annonce')
            ->where('source_type', Annonce::class)
            ->where('source_id', $annonce->id)
            ->first();

        abort_unless($notification, 403, 'Cette annonce ne vous concerne pas.');

        $notification->marquerCommeLue();
        $annonce->load(['auteur', 'classe']);

        return $this->success('Annonce récupérée avec succès.', [
            'annonce' => $this->formatAnnonce($annonce, $notification),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'statut' => ['nullable', Rule::in(['non_lues', 'lues'])],
            'type' => ['nullable', 'string', Rule::in(array_keys(NotificationUtilisateur::TYPES))],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = $validated['limit'] ?? 50;

        $query = $request->user()
            ->notificationsUtilisateur()
            ->with('source')
            ->when(($validated['statut'] ?? null) === 'non_lues', fn ($q) => $q->nonLues())
            ->when(($validated['statut'] ?? null) === 'lues', fn ($q) => $q->lues())
            ->when($validated['type'] ?? null, fn ($q, $type) => $q->where('type', $type));

        $totalNonLues = $request->user()
            ->notificationsUtilisateur()
            ->nonLues()
            ->count();

        $notifications = $query
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (NotificationUtilisateur $notification) => $this->formatNotification($notification));

        return $this->success('Notifications récupérées avec succès.', [
            'total_non_lues' => $totalNonLues,
            'total' => $notifications->count(),
            'notifications' => $notifications,
        ]);
    }

    public function notificationsNonLues(Request $request): JsonResponse
    {
        $total = $request->user()
            ->notificationsUtilisateur()
            ->nonLues()
            ->count();

        return $this->success('Nombre de notifications non lues récupéré avec succès.', [
            'total_non_lues' => $total,
        ]);
    }

    public function notification(Request $request, NotificationUtilisateur $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403, 'Cette notification ne vous appartient pas.');

        $notification->load('source');
        $notification->marquerCommeLue();

        return $this->success('Notification récupérée avec succès.', [
            'notification' => $this->formatNotification($notification),
        ]);
    }

    public function marquerNotificationLue(Request $request, NotificationUtilisateur $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403, 'Cette notification ne vous appartient pas.');

        $notification->marquerCommeLue();

        return $this->success('Notification marquée comme lue.', [
            'notification' => $this->formatNotification($notification),
        ]);
    }

    public function toutMarquerCommeLu(Request $request): JsonResponse
    {
        $request->user()
            ->notificationsUtilisateur()
            ->nonLues()
            ->update([
                'lue' => true,
                'lue_le' => now(),
            ]);

        return $this->success('Toutes les notifications ont été marquées comme lues.');
    }

    private function formatAnnonce(Annonce $annonce, ?NotificationUtilisateur $notification = null): array
    {
        return [
            'id' => $annonce->id,
            'titre' => $annonce->titre,
            'contenu' => $annonce->contenu,
            'type' => $annonce->type,
            'libelle_type' => $annonce->libelleType(),
            'priorite' => $annonce->priorite,
            'libelle_priorite' => $annonce->libellePriorite(),
            'cible' => $annonce->cible,
            'libelle_cible' => $annonce->libelleCible(),
            'classe' => $annonce->classe ? [
                'id' => $annonce->classe->id,
                'niveau' => $annonce->classe->niveau,
                'nom' => $annonce->classe->nom,
            ] : null,
            'auteur' => $annonce->auteur ? [
                'id' => $annonce->auteur->id,
                'nom' => $annonce->auteur->nom,
                'prenom' => $annonce->auteur->prenom,
                'name' => $annonce->auteur->name,
            ] : null,
            'date_publication' => $this->dateTimeValue($annonce->date_publication),
            'date_expiration' => $this->dateTimeValue($annonce->date_expiration),
            'est_expiree' => $annonce->estExpiree(),
            'notification' => $notification ? [
                'id' => $notification->id,
                'lue' => (bool) $notification->lue,
                'lue_le' => $this->dateTimeValue($notification->lue_le),
            ] : null,
        ];
    }

    private function formatNotification(NotificationUtilisateur $notification): array
    {
        return [
            'id' => $notification->id,
            'titre' => $notification->titre,
            'message' => $notification->message,
            'type' => $notification->type,
            'libelle_type' => $notification->libelleType(),
            'lien' => $notification->lien,
            'lue' => (bool) $notification->lue,
            'lue_le' => $this->dateTimeValue($notification->lue_le),
            'email_mode' => $notification->email_mode,
            'email_resume' => $notification->email_resume,
            'email_raison_connexion' => $notification->email_raison_connexion,
            'email_statut' => $notification->email_statut,
            'email_envoye_le' => $this->dateTimeValue($notification->email_envoye_le),
            'metadata' => $notification->metadata,
            'source' => $this->formatSource($notification),
            'cree_le' => $this->dateTimeValue($notification->created_at),
        ];
    }

    private function formatSource(NotificationUtilisateur $notification): ?array
    {
        if (! $notification->source) {
            return null;
        }

        if ($notification->source instanceof Annonce) {
            return [
                'type' => 'annonce',
                'id' => $notification->source->id,
                'titre' => $notification->source->titre,
            ];
        }

        return [
            'type' => class_basename($notification->source_type),
            'id' => $notification->source_id,
        ];
    }

    private function dateTimeValue($value): ?string
    {
        if (! $value) {
            return null;
        }

        return method_exists($value, 'toDateTimeString') ? $value->toDateTimeString() : (string) $value;
    }

    private function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
