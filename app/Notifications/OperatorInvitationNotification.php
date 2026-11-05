<?php

namespace App\Notifications;

use App\Models\OperatorInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OperatorInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public OperatorInvitation $invitation,
        public string $signedUrl
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Q4I Clearinghouse] Operator Staff Authorization Invitation')
            ->greeting('Authorization Invitation')
            ->line("You have been invited to join the Q4I Clearinghouse platform as an authorized {$this->invitation->role}.")
            ->action('Complete Staff Registration', $this->signedUrl)
            ->line('This cryptographic invitation is valid for 3 days and binds your credentials to your assigned operator.')
            ->line('If you did not expect this invitation, please ignore this notice.');
    }
}

