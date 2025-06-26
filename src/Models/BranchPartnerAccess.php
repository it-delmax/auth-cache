<?php

namespace ItDelmax\AuthCache\Models;

use ItDelmax\AuthCache\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use ItDelmax\AuthCache\Models\Partner;
use ItDelmax\AuthCache\Models\BranchAccessType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class BranchPartnerAccess extends Model
{

  const CREATED_AT = 'CREATED_AT';
  const UPDATED_AT = 'UPDATED_AT';

  protected $connection = 'etg';

  protected $table = 'BRANCH_PARTNER_ACCESS';

  protected $primaryKey = 'ID';

  protected $fillable = [
    'COMPANY_ID',
    'BRANCH_ID',
    'PARTNER_ID',
    'IS_ACTIVE',
    'PRIORITY',
    'BRANCH_ACCESS_TYPE_ID'
  ];

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnectionName();
  }

  public function branch(): BelongsTo
  {
    return $this->belongsTo(Branch::class, 'BRANCH_ID', 'BRANCH_ID');
  }

  public function partner(): BelongsTo
  {
    return $this->belongsTo(Partner::class, 'PARTNER_ID', 'PARTNER_ID');
  }

  public function accessType(): BelongsTo
  {
    return $this->belongsTo(BranchAccessType::class, 'BRANCH_ACCESS_TYPE_ID', 'ID');
  }
}
