<?php

namespace ItDelmax\AuthCache\Models;

use ItDelmax\AuthCache\Models\Branch;
use ItDelmax\AuthCache\Models\Partner;
use ItDelmax\AuthCache\Models\Uposljeni;
use ItDelmax\AuthCache\Models\AccountType;
use ItDelmax\AuthCache\Models\Traits\CachesRelationships;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use ItDelmax\AuthCache\Models\Traits\DmxApiAccess;
use ItDelmax\AuthCache\Models\Traits\DmxHasApiTokens;
use ItDelmax\AuthCache\Notifications\ResetPasswordNotification;
use ItDelmax\AuthCache\Notifications\VerifyEmailNotification;
use ItDelmax\AuthCache\Services\TokenCacheService;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
  use HasFactory,
    Notifiable,
    HasApiTokens,
    DmxApiAccess,
    DmxHasApiTokens,
    CachesRelationships,
    HasRoles,
    HasPermissions;

  protected $connection = 'etg_utf8';
  protected $table = 'USERS_VIEW';
  protected $primaryKey = 'user_id';

  public function __construct(array $attributes = [])
  {
    parent::__construct($attributes);

    $this->connection = config('auth_cache.connection') ?: parent::getConnectionName();

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
    'partner_id',
    'employee_id',
    'created_by',
    'updated_by',
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

  protected $appends = ['full_name', 'subjekt_id'];


  public function getFullNameAttribute()
  {
    return $this->name;
  }

  public function getSubjektIdAttribute()
  { // Returns the SUBJEKT_ID based on account type
    // 1 = Employee, 2 = Branch, 3 = Partner 4 = External user
    if ($this->account_type_id == 1) {
      return $this->uposljeni?->SUBJEKT_ID;
    }

    if ($this->account_type_id == 2) {
      return $this->partner_id;
    }

    return null;
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
    return $this->belongsTo(Uposljeni::class, 'employee_id', 'UPOSLJENI_ID');
  }

  public function partner(): BelongsTo
  {
    return $this->belongsTo(Partner::class, 'partner_id', 'PARTNER_ID');
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

  public function isPartner(): bool
  {
    return $this->account_type_id === 3;
  }

  public function resolvedPartner(): ?Partner
  {
    return $this->isPartner() ? $this->partner : null;
  }

  public function isBranch(): bool
  {
    return $this->account_type_id === 2;
  }

  public function sendPasswordResetNotification($token): void
  {
    $this->notify(new ResetPasswordNotification($token));
  }

  public function sendEmailVerificationNotification(): void
  {
    $this->notify(new VerifyEmailNotification());
  }

  /**
   * Get user type name
   */
  public function getUserTypeName(): string
  {
    return match ($this->account_type_id) {
      1 => 'Employee',
      2 => 'Branch',
      3 => 'Partner',
      4 => 'External user',
      default => 'Unknown',
    };
  }

  /**
   * Override Sanctum's currentAccessToken to work with DMX system
   */
  public function currentAccessToken()
  {
    $token = request()->bearerToken();

    if (!$token) {
      return null;
    }

    $parts = explode('|', $token, 2);
    if (count($parts) !== 2) {
      return response()->json(['message' => 'Invalid token format'], 401);
    }

    $plainTextToken = $parts[1];

    $hashedToken = hash('sha256', $plainTextToken);

    $cacheService = app(TokenCacheService::class);
    $cachedToken = $cacheService->getCachedToken($hashedToken);

    if ($cachedToken) {
      return (object) [
        'id' => $cachedToken['token_id'],
        'name' => 'Cached Token', // možda dodati name u cache
        'abilities' => $cachedToken['abilities'] ?? [],
        'token' => $token,
        'last_used_at' => null,
        'expires_at' => $cachedToken['expires_at'],
        'type' => 'dmx',
        'source' => 'cache'
      ];
    }

    // DRUGO - ako nije u cache-u, traži u bazi
    $dmxToken = PersonalAccessToken::where('token', $hashedToken)->first();

    if ($dmxToken) {
      $abilities = $dmxToken->ABILITIES ? json_decode($dmxToken->ABILITIES, true) : [];

      // CACHE token za sledeći put
      $cacheService->cacheToken($dmxToken);

      return (object) [
        'id' => $dmxToken->id,
        'name' => $dmxToken->name,
        'abilities' => $abilities,
        'token' => $token,
        'last_used_at' => $dmxToken->last_used_at,
        'expires_at' => $dmxToken->expires_at,
        'type' => 'dmx',
        'source' => 'database'
      ];
    }

    // Fallback na parent Sanctum metodu
    return parent::currentAccessToken();
  }

  /**
   * Override tokenCan to work with DMX abilities
   */
  public function tokenCan(string $ability): bool
  {
    $currentToken = $this->currentAccessToken();

    if (!$currentToken) {
      return false;
    }

    // Ako je DMX token
    if (isset($currentToken->type) && $currentToken->type === 'dmx') {
      $abilities = $currentToken->abilities ?? [];
      return in_array('*', $abilities) || in_array($ability, $abilities);
    }

    // Fallback na parent za Sanctum tokene
    return parent::tokenCan($ability);
  }

  /**
   * Get current token abilities
   */
  public function getCurrentTokenAbilities(): array
  {
    $currentToken = $this->currentAccessToken();
    return $currentToken ? ($currentToken->abilities ?? []) : [];
  }
}
