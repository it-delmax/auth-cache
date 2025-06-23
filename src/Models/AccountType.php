<?php

namespace ItDelmax\AuthCache\Models;

use ItDelmax\AuthCache\Models\User;
use Firebird\Eloquent\Model;

class AccountType extends Model
{
  protected $connection = 'etg_utf8';

  protected $table = 'ACCOUNT_TYPES';

  protected $primaryKey = 'ID';

  protected $fillable = [
    'NAME',
  ];


  public function users()
  {
    return $this->hasMany(User::class, 'ACCOUNT_TYPE_ID');
  }
}
