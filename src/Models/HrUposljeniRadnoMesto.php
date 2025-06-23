<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;

class HrUposljeniRadnoMesto extends Model
{
  const CREATED_AT = 'CREATED_AT';
  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg_utf8';

  protected $table = 'HR_UPOSLJENI_RADNO_MESTO';

  protected $primaryKey = 'HR_UPOSLJENI_RADNO_MESTO_ID';

  public $incrementing = false;
}
