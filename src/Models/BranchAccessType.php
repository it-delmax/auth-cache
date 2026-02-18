<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;
use ItDelmax\AuthCache\Models\BranchPartnerAccess;

class BranchAccessType extends Model
{
  protected $connection = 'etg_utf8';
  protected $table = 'BRANCH_ACCESS_TYPE';
  protected $primaryKey = 'ID';

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth-cache.connection') ?: parent::getConnectionName();
  }


  public function branchPartnerAccess()
  {
    return $this->hasMany(BranchPartnerAccess::class, 'BRANCH_ACCESS_TYPE_ID', 'ID');
  }
}
