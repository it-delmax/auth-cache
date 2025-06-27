<?php


namespace ItDelmax\AuthCache\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminGeneratedTokenMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;


  public array $tokenData;

  public function __construct(array $tokenData)
  {
    $this->tokenData = $tokenData;
  }

  public function build()
  {
    return $this->view('emails.admin-generated-token')
      ->subject('🔑 Vaš API pristupni token - ' . $this->tokenData['token_name'])
      ->with($this->tokenData);
  }
}
