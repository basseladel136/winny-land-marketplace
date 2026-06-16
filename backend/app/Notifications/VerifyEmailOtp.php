<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailOtp extends Notification
{
    use Queueable;

    public function __construct(public string $otp, public int $expiresInMinutes = 10) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Winny Land verification code')
            ->greeting('Welcome to Winny Land!')
            ->line('Use the following code to verify your email address and activate your account:')
            ->line('## ' . $this->otp)
            ->line("This code expires in {$this->expiresInMinutes} minutes.")
            ->line('If you did not create an account, you can safely ignore this email.');
    }
}
