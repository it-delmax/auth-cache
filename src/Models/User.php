<?php

namespace ItDelmax\AuthCache\Models;

use ItDelmax\AuthCache\Models\Branch;
use ItDelmax\AuthCache\Models\Partner;
use ItDelmax\AuthCache\Models\Uposljeni;
use ItDelmax\AuthCache\Models\AccountType;
use ItDelmax\AuthCache\Models\EtgApi;
use ItDelmax\AuthCache\Models\EtgApiUser;
use ItDelmax\AuthCache\Models\Traits\CachesRelationships;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable implements MustVerifyEmail
{
  use HasFactory, Notifiable, HasApiTokens, CachesRelationships;

  protected $connection = 'etg_utf8';
  protected $table = 'USERS_VIEW';
  protected $primaryKey = 'user_id';

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnection();

    $this->table = config('auth_cache.tables.users') ?: parent::getTable();
  }

  protected $fillable = [
    'name',
    'email',
    'username',
    'phone',
    'email_verified_at',
    'account_type_id',
    'password',
    'erp_id',
    'remember_token',
  ];

  protected $hidden = ['password', 'remember_token'];

  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  protected $appends = ['full_name', 'subjekt_id', 'partner_id'];

  /** ------------------- Accessors ------------------- **/

  public function getFullNameAttribute()
  {
    return $this->name;
  }

  public function getSubjektIdAttribute()
  {
    if ($this->account_type_id !== 1) {
      return null;
    }

    return $this->uposljeni?->SUBJEKT_ID;
  }

  public function getPartnerIdAttribute()
  {
    if ($this->account_type_id !== 3) {
      return null;
    }

    return $this->partner?->PARTNER_ID;
  }

  /** ------------------- Relacije ------------------- **/

  public function accountType(): BelongsTo
  {
    return $this->belongsTo(AccountType::class, 'account_type_id', 'ID')
      ->withDefault([
        'NAME' => 'Unknown',
      ]);
  }

  public function uposljeni(): BelongsTo
  {
    return $this->belongsTo(Uposljeni::class, 'erp_id', 'UPOSLJENI_ID');
  }

  public function partner(): BelongsTo
  {
    return $this->belongsTo(Partner::class, 'erp_id', 'PARTNER_ID');
  }

  /** ------------------- API & Prava Pristupa ------------------- **/

  public function apiAccess(): HasMany
  {
    return $this->hasMany(EtgApiUser::class, 'USER_ID', 'user_id')
      ->where('IS_ACTIVE', 1)
      ->where(function ($query) {
        $query->whereNull('EXPIRES_AT')
          ->orWhere('EXPIRES_AT', '>', now());
      });
  }

  public function activeApiAccess(): HasMany
  {
    return $this->apiAccess(); // reuse
  }

  public function branchAccess(): HasMany
  {
    return $this->hasMany(Branch::class, 'USER_ID', 'user_id')
      ->where('IS_ACTIVE', 1)
      ->where(function ($query) {
        $query->whereNull('EXPIRES_AT')
          ->orWhere('EXPIRES_AT', '>', now());
      });
  }

  /** ------------------- Helper Metode ------------------- **/

  public function hasVerifiedEmail()
  {
    return !is_null($this->email_verified_at);
  }

  public function markEmailAsVerified()
  {
    $this->email_verified_at = now();
    return $this->save();
  }

  public function hasApiAccess($apiSlug): bool
  {
    return $this->activeApiAccess()
      ->whereHas('api', fn($q) => $q->where('SLUG', $apiSlug)->where('IS_ACTIVE', 1))
      ->exists();
  }

  public function getAccessibleApis()
  {
    return EtgApi::whereHas('apiUsers', function ($query) {
      $query->where('USER_ID', $this->user_id)
        ->where('IS_ACTIVE', 1)
        ->where(function ($q) {
          $q->whereNull('EXPIRES_AT')
            ->orWhere('EXPIRES_AT', '>', now());
        });
    })->where('IS_ACTIVE', 1)->get();
  }

  public function hasBranchAccess($branchId): bool
  {
    return $this->branchAccess()
      ->where('BRANCH_ID', $branchId)
      ->exists();
  }

  public function isPartner(): bool
  {
    return $this->account_type_id === 3;
  }

  public function resolvedPartner(): ?Partner
  {
    return $this->isPartner() ? $this->partner : null;
  }
}
