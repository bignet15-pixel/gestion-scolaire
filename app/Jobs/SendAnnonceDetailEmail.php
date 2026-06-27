<?php

namespace App\Jobs;

use App\Mail\AnnonceDetailMail;
use App\Models\Annonce;
use App\Models\NotificationUtilisateur;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAnnonceDetailEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;

    public function __construct(
        public int $notificationId,
        public int $annonceId,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $notification = NotificationUtilisateur::with('user')->find($this->notificationId);
        $annonce = Annonce::find($this->annonceId);

        if (! $notification || ! $annonce) {
            return;
        }

        if (! $notification->user?->email) {
            $notification->update([
                'email_statut' => 'failed',
                'email_erreur' => 'Aucune adresse email disponible pour le destinataire.',
            ]);

            return;
        }

        Mail::to($notification->user->email)->send(new AnnonceDetailMail($annonce));

        $notification->update([
            'email_statut' => 'sent',
            'email_envoye_le' => now(),
            'email_erreur' => null,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        NotificationUtilisateur::whereKey($this->notificationId)->update([
            'email_statut' => 'failed',
            'email_erreur' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
