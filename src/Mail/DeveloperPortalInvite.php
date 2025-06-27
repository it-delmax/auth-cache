<?php

namespace ItDelmax\AuthCache\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeveloperPortalInvite extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public array $inviteData;

  public function __construct(array $inviteData)
  {
    $this->inviteData = $inviteData;
  }

  public function build()
  {
    return $this->view('emails.developer-portal-invite')
      ->subject('🚀 Poziv za Delmax Developer Portal')
      ->with($this->inviteData);
  }
}
