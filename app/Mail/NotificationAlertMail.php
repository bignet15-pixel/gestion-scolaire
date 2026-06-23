<?php

namespace App\Mail;

use App\Models\NotificationUtilisateur;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NotificationUtilisateur $notification
    ) {
    }

    public function build(): self
    {
        return $this->subject($this->notification->titre.' - '.config('ecole.nom'))
            ->view('emails.notifications.alert')
            ->with([
                'notification' => $this->notification,
                'ecoleNom' => config('ecole.nom'),
            ]);
    }
}
