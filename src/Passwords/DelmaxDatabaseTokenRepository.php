<?php

namespace ItDelmax\AuthCache\Passwords;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DelmaxDatabaseTokenRepository extends DatabaseTokenRepository
{
  /**
   * Upis tokena direktnim INSERT-om (bez updateOrInsert).
   */
  public function create(CanResetPassword $user)
  {
    $email = $user->getEmailForPasswordReset();

    $token = $this->createNewToken();

    DB::table($this->table)->insert([
      'email'      => $email,
      'token'      => $this->hasher->make($token),
      'created_at' => now(),
    ]);

    return $token; // mora vratiti plain token koji će se poslati korisniku
  }
}
