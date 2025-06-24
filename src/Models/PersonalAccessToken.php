<?php

namespace ItDelmax\AuthCache\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
  protected $connection = 'etg_utf8';
  protected $table = 'PERSONAL_ACCESS_TOKENS';
  protected $primaryKey = 'id';

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnectionName();
  }
}
