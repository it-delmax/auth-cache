<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subjekt extends Model
{
  const CREATED_AT = 'CREATED_AT';

  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg_utf8';

  protected $table = 'SUBJEKT';

  protected $primaryKey = 'SUBJEKT_ID';

  public $incrementing = false;

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnectionName();
  }

  public function scopeAktivan($query): void
  {
    $query->where('AKTIVAN', 1);
  }

  public function scopePoslovnice($query): void
  {
    $query->whereIn('VRSTA_SUBJEKTA_ID', ['CPJ', 'ZPJ', 'DC']);
  }
}
