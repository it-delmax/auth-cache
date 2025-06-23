<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;

class Drzava extends Model
{
  const CREATED_AT = 'CREATED_AT';

  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg_utf8';

  protected $table = 'DRZAVA';

  protected $primaryKey = 'DRZAVA_ID';

  public $incrementing = false;
}
