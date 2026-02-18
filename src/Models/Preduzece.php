<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Preduzece extends Model
{
  const CREATED_AT = 'CREATED_AT';

  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg_utf8';

  protected $table = 'PREDUZECE';

  protected $primaryKey = 'PREDUZECE_ID';

  protected $keyType = 'string';

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth-cache.connection') ?: parent::getConnectionName();
  }
}
