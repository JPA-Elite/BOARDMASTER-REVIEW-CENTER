<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class MobileResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $actionUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Reset your BoardMasters password')
            ->view('emails.mobile.reset-password', [
                'name' => $notifiable->name ?? 'Review Student',
                'email' => $notifiable->getEmailForPasswordReset(),
                'actionUrl' => $actionUrl,
                'appName' => config('app.name', 'BoardMasters Review Center'),
                'expireMinutes' => (int) config('auth.passwords.users.expire', 60),
            ]);
    }
}
