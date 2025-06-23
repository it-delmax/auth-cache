<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Model;

class BranchAccessType extends Model
{
  protected $connection = 'etg_utf8';
  protected $table = 'BRANCH_ACCESS_TYPE';
  protected $primaryKey = 'ID';
}
