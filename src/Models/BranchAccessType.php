<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;

class BranchAccessType extends Model
{
  protected $connection = 'etg_utf8';
  protected $table = 'BRANCH_ACCESS_TYPE';
  protected $primaryKey = 'ID';

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnection();
  }
}
