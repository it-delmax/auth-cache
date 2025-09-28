<?php

namespace ItDelmax\AuthCache\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Notification implements ShouldQueue
{
  use Queueable;

  public function via($notifiable): array
  {
    return ['mail'];
  }

  protected function verificationUrl($notifiable): string
  {
    $expires = now()->addMinutes(config('auth-cache.mail.expires.verify', 60));

    // Backend potpisani link:
    $signed = URL::temporarySignedRoute(
      'verification.verify',
      $expires,
      [
        'id'   => $notifiable->getKey(),
        'hash' => sha1($notifiable->getEmailForVerification()),
      ]
    );

    // Ako želiš SPA link, prosledi parametre:
    $frontend = rtrim((string) config('auth-cache.mail.frontend.verify_url'), '/');

    return $frontend
      ? $frontend . '?id=' . $notifiable->getKey() . '&hash=' . sha1($notifiable->getEmailForVerification()) . '&signature=' . urlencode(parse_url($signed, PHP_URL_QUERY))
      : $signed;
  }

  public function toMail($notifiable): MailMessage
  {
    $subject = str_replace(':app', config('app.name'), config('auth-cache.mail.subjects.verify_email'));

    return (new MailMessage)
      ->subject($subject)
      ->markdown('auth-cache::emails.verify-email', [
        'user' => $notifiable,
        'url'  => $this->verificationUrl($notifiable),
        'app'  => config('app.name'),
      ]);
  }
}
