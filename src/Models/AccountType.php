<?php

namespace ItDelmax\AuthCache\Models;

use ItDelmax\AuthCache\Models\User;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
  protected $connection = 'etg_utf8';

  protected $table = 'ACCOUNT_TYPES';

  protected $primaryKey = 'ID';

  protected $fillable = [
    'NAME',
  ];

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnectionName();
  }

  public function users()
  {
    return $this->hasMany(User::class, 'ACCOUNT_TYPE_ID');
  }
}
