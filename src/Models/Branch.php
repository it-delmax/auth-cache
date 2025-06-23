<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
  const CREATED_AT = null;
  const UPDATED_AT = null;

  protected $connection = 'etg_utf8';

  protected $table = 'BRANCHES_VIEW';

  protected $primaryKey = 'BRANCH_ID';

  public $incrementing = false;

  protected $fillable = [
    'MOBILE',
  ];

  public function getAttribute($key)
  {
    if ($key === 'MOBILE') {
      return  iconv('Windows-1250', 'UTF-8', $this->attributes['MOBILE']);
    }

    return parent::getAttribute($key);
  }
}
