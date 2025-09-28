<?php

namespace ItDelmax\AuthCache\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(public string $token) {}

  public function via($notifiable): array
  {
    return ['mail'];
  }

  public function toMail($notifiable): MailMessage
  {
    $subject = str_replace(':app', config('app.name'), config('auth-cache.email.subjects.reset_password'));

    // Ako želiš SPA link:
    $frontend = rtrim((string) config('auth-cache.mail.frontend.reset_url'), '/');
    $url = $frontend
      ? $frontend . '?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset())
      : url(route('password.reset', [
        'token' => $this->token,
        'email' => $notifiable->getEmailForPasswordReset(),
      ], false));

    return (new MailMessage)
      ->subject($subject)
      ->markdown('auth-cache::emails.reset-password', [
        'user'    => $notifiable,
        'url'     => $url,
        'app'     => config('app.name'),
        'minutes' => config('auth-cache.mail.expires.reset', 60),
      ]);
  }
}
