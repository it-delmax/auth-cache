<?php

namespace ItDelmax\AuthCache\Passwords;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;

class CustomPasswordBroker extends PasswordBroker
{
  protected function createToken($user)
  {
    $email = $user->getEmailForPasswordReset();
    $token = $this->createNewToken();

    // Bez updateOrInsert - direktan insert
    DB::table($this->tokens)->insert([
      'email' => $email,
      'token' => $this->hasher->make($token),
      'created_at' => now(),
    ]);

    return $token;
  }
}
