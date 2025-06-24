<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;
use ItDelmax\AuthCache\Models\HrUposljeniRadnoMesto;
use ItDelmax\AuthCache\Models\BranchAccessType;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Uposljeni extends Model
{
  const CREATED_AT = 'CREATED_AT';
  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg_utf8';

  protected $table = 'UPOSLJENI';

  protected $primaryKey = 'UPOSLJENI_ID';

  protected $keyType = 'string';

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnection();
  }

  public function radnaMesta(): HasMany
  {
    return $this->hasMany(HrUposljeniRadnoMesto::class, 'UPOSLJENI_ID', 'UPOSLJENI_ID');
  }

  public function scopeVozaci($query): void
  {
    $query->whereHas('radnaMesta', function ($query) {
      $query->where('HR_RADNO_MESTO_ID', 13);
    });
  }

  public function scopeAktivan($query): void
  {
    $query->whereNull('DATUM_PRESTANKA_RADNOG_ODNOSA');
  }
}
