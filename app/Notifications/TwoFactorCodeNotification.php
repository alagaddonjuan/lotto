<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Q4I Clearinghouse] Your 6-Digit Two-Factor Authentication Code')
            ->greeting("Hello, {$notifiable->name}")
            ->line('You are receiving this notification because a login attempt on the Q4I Clearinghouse requires two-factor identity verification.')
            ->line('Your one-time authentication code is:')
            ->line("# **{$this->code}**")
            ->line('This code is strictly valid for 10 minutes. Never share this code with anyone.')
            ->line('If you did not initiate this login request, please alert your security team immediately.');
    }
}
