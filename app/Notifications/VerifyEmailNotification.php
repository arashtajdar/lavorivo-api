<?php
namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;

class VerifyEmailNotification extends VerifyEmail
{
    protected function verificationUrl($notifiable)
    {
        $prefix = rtrim(config('app.frontend_url'), '/');

        $temporarySignedURL = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return str_replace('/api/api/', '/api/', $prefix . '/api' . parse_url($temporarySignedURL, PHP_URL_PATH) . '?' . parse_url($temporarySignedURL, PHP_URL_QUERY));
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name') . '! Verify Your Email')
            ->view('emails.verify-email', [  // Changed from markdown to view
                'url' => $verificationUrl,
                'user' => $notifiable,
                'count' => config('auth.verification.expire', 60)
            ]);
    }
}
