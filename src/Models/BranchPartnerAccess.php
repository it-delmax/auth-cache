<?php

namespace ItDelmax\AuthCache\Models;

use ItDelmax\AuthCache\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use ItDelmax\AuthCache\Models\Partner;
use ItDelmax\AuthCache\Models\BranchAccessType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class BranchPartnerAccess extends Model
{
  protected $connection = 'etg';

  protected $table = 'BRANCH_PARTNER_ACCESS';

  protected $primaryKey = 'ID';

  protected $fillable = [
    'BRANCH_ID',
    'PARTNER_ID',
    'IS_ACTIVE',
    'PRIORITY',
    'BRANCH_ACCESS_TYPE_ID',
    'CREATED_AT',
    'UPDATED_AT',
  ];

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnection();
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
    return $this->belongsTo(BranchAccessType::class, 'ID', 'BRANCH_ACCESS_TYPE_ID');
  }
}
