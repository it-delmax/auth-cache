<?php

namespace ItDelmax\AuthCache\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ItDelmax\AuthCache\Models\Subjekt;
use ItDelmax\AuthCache\Models\Preduzece;
use ItDelmax\AuthCache\Models\BranchPartnerAccess;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;


class Partner extends Model
{
  const CREATED_AT = 'CREATED_AT';

  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg_utf8';

  protected $table = 'PARTNER';

  protected $primaryKey = 'PARTNER_ID';

  public $incrementing = false;

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnectionName();
  }

  public function preduzece(): BelongsTo
  {
    return $this->belongsTo(Preduzece::class, 'PREDUZECE_ID', 'PREDUZECE_ID');
  }

  public function subjekt(): BelongsTo
  {
    return $this->belongsTo(Subjekt::class, 'SUBJEKT_ID', 'PARTNER_ID');
  }

  public function branchAccess(): HasMany
  {
    return $this->hasMany(BranchPartnerAccess::class, 'PARTNER_ID', 'PARTNER_ID');
  }
}
