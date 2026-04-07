<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class MobileVerifyEmailNotification extends VerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'api.mobile.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    public function toMail($notifiable): MailMessage
    {
        $actionUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify your BoardMasters email')
            ->view('emails.mobile.verify-email', [
                'name' => $notifiable->name ?? 'Review Student',
                'email' => $notifiable->getEmailForVerification(),
                'actionUrl' => $actionUrl,
                'appName' => config('app.name', 'BoardMasters Review Center'),
                'expireMinutes' => (int) Config::get('auth.verification.expire', 60),
            ]);
    }
}
