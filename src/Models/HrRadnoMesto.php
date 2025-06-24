<?php

namespace App\Models\Etg;

use Illuminate\Database\Eloquent\Model;

class HrRadnoMesto extends Model
{
  const CREATED_AT = 'CREATED_AT';
  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg_utf8';

  protected $table = 'HR_RADNO_MESTO';

  protected $primaryKey = 'HR_RADNO_MESTO_ID';

  public $incrementing = false;

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnection();
  }
}
