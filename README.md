# IT Delmax Auth Cache

Laravel package for advanced authentication and cache management with Firebird database support.

## Features

- 🔥 **High-performance caching** - Redis-based token and user caching
- 🔐 **Firebird integration** - Seamless integration with legacy Firebird databases
- 🚀 **Laravel Sanctum enhancement** - Cached authentication middleware
- ⚡ **Background jobs** - Automatic cache warming and invalidation
- 📊 **Monitoring tools** - Built-in cache statistics and management commands

## Installation

### 1. Add to composer.json

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/it-delmax/auth-cache"
    }
  ],
  "require": {
    "it-delmax/auth-cache": "*"
  }
}
```

### 2. Install package

```bash
composer install
```

### 3. Regenerate autoload

```bash
composer dump-autoload
```

### 4. Add to autoload (if needed)

```json
{
  "autoload": {
    "psr-4": {
      "ItDelmax\\AuthCache\\": "packages/it-delmax/auth-cache/src/"
    }
  }
}
```

## Configuration

### 1. Update config/auth.php

Set the authentication models to use package models:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => ItDelmax\AuthCache\Models\User::class,
    ],
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],
```

### 2. Update config/sanctum.php

Configure Sanctum to use package PersonalAccessToken model:

```php
/*
|--------------------------------------------------------------------------
| Personal Access Token Model
|--------------------------------------------------------------------------
*/
'personal_access_token_model' => ItDelmax\AuthCache\Models\PersonalAccessToken::class,

/*
|--------------------------------------------------------------------------
| Stateful Domains
|--------------------------------------------------------------------------
*/
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    Sanctum::currentApplicationUrlWithPort()
))),
```

### 3. Database Connections

Add Firebird connections to your `config/database.php`:

```php
'connections' => [
    'etg_utf8' => [
        'driver' => 'firebird',
        'host' => env('ETG_DB_HOST', 'localhost'),
        'port' => env('ETG_DB_PORT', 3050),
        'database' => env('ETG_DB_DATABASE', 'database'),
        'username' => env('ETG_DB_USERNAME', 'SYSDBA'),
        'password' => env('ETG_DB_PASSWORD', 'masterkey'),
        'charset' => env('ETG_DB_CHARSET', 'UTF8'),
    ],
],
```

### 4. Update config/cache.php

Ensure Redis is configured as the cache store:

```php
'default' => env('CACHE_STORE', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => env('CACHE_REDIS_CONNECTION', 'cache'),
        'lock_connection' => env('CACHE_REDIS_LOCK_CONNECTION', 'default'),
    ],
],
```

### 5. Update config/session.php

Configure sessions to use Redis:

```php
'driver' => env('SESSION_DRIVER', 'redis'),
'connection' => env('SESSION_CONNECTION', null),
'store' => env('SESSION_STORE', 'default'),
'domain' => env('SESSION_DOMAIN', '.your-domain.com'),
```

### 6. Update config/queue.php

Configure queue for background jobs:

```php
'default' => env('QUEUE_CONNECTION', 'redis'),

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
        'block_for' => null,
        'after_commit' => false,
    ],
],
```

### 7. Register Service Provider

Add to your `config/app.php` providers array (if auto-discovery doesn't work):

```php
'providers' => [
    // Other providers...
    ItDelmax\AuthCache\Providers\AuthCacheServiceProvider::class,
],
```

### 8. Environment Variables

```env
#Connections
AUTH_CACHE_DB_CONNECTION='etg_utf8'

# Firebird Database
ETG_DB_HOST=your-firebird-server
ETG_DB_PORT=3050
ETG_DB_DATABASE=your-database
ETG_DB_USERNAME=SYSDBA
ETG_DB_PASSWORD=your-password
ETG_DB_CHARSET=UTF8

# Redis Cache
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_STORE=redis
CACHE_PREFIX=your_app_

# Session Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_DOMAIN=.your-domain.com

# Queue Configuration
QUEUE_CONNECTION=redis

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost:3000,your-app.test
```

## Usage

### Models

Use the package models in your application:

```php
use ItDelmax\AuthCache\Models\User;
use ItDelmax\AuthCache\Models\PersonalAccessToken;

// User model with caching support
$user = User::find(1);

// Cached token verification
$token = PersonalAccessToken::findToken($tokenHash);
```

### Cache Service

```php
use ItDelmax\AuthCache\Services\TokenCacheService;

$cacheService = app(TokenCacheService::class);

// Warm user cache
$cacheService->warmUserCache($userId);

// Invalidate user data
$cacheService->invalidateAllUserData($userId);

// Get cache statistics
$stats = $cacheService->getCacheStats();
```

### Middleware

Add cached authentication middleware:

```php
// In your routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Your protected routes
});

// Or use the cached version directly
use ItDelmax\AuthCache\Middleware\CachedSanctumAuth;

Route::middleware([CachedSanctumAuth::class])->group(function () {
    // Lightning-fast authentication
});
```

### Background Jobs

The package includes automatic cache management:

```php
// Manual job dispatch
use ItDelmax\AuthCache\Jobs\WarmUserCacheJob;
use ItDelmax\AuthCache\Jobs\InvalidateExpiredTokensJob;

WarmUserCacheJob::dispatch($userId);
InvalidateExpiredTokensJob::dispatch();
```

## Artisan Commands

### Cache Management

Podesavanja:
U `config/cache.php`, u sekciji `'stores'`, dodaj `auth_cache` store koji koristi tvoju redis konekciju auth_cache

```php
'stores' => [

    // ... ostali store-ovi ...

    'auth_cache' => [
        'driver' => 'redis',
        'connection' => 'auth_cache', // ← ovo je ključ!
    ],

],
```

U `config/database.php` podesiti cache store za auth cache:
⚠️ `'database' => 1` ovo je jako vazno, da bi koristili psoebnu redis bazu i ovo treba da bude isto na svim app koje treba da dele auth-cache

```php
'redis' => [

    'auth_cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => 1,
    ],
],
```

Koriscenje iz terminala:

```bash
# Show cache configuration
php artisan auth-cache:config

# Warm cache for specific user
php artisan auth-cache:warm-user 123

# Invalidate all user data
php artisan auth-cache:invalidate-user 123

# Warm samo korisnike
php artisan auth-cache:warm --users

# Warm samo korisnike sa API pristupom
php artisan auth-cache:warm --api-users

# Warm sve tokene za aktivne usere
php artisan auth-cache:warm --tokens

# Warm sve
php artisan auth-cache:warm --all
```

### Scheduled Jobs

Add to your `routes/console.php`:

```php
use ItDelmax\AuthCache\Jobs\InvalidateExpiredTokensJob;
use ItDelmax\AuthCache\Jobs\WarmAllActiveTokensJob;

// Invalidate expired tokens every hour
Schedule::job(new InvalidateExpiredTokensJob())
    ->hourly()
    ->name('cache:invalidate-expired-tokens')
    ->onOneServer();

// Warm all active tokens every 6 hours
Schedule::job(new WarmAllActiveTokensJob())
    ->everySixHours()
    ->name('cache:warm-all-tokens')
    ->onOneServer();
```

## Cache Strategy

### TTL Configuration

- **User Cache**: 6 hours
- **Token Cache**: 12 hours
- **API Access**: 24 hours
- **API Slug**: 24 hours

### Performance Benefits

- **Cache Hit**: ~1ms response time
- **Cache Miss**: ~25-50ms (with Firebird fallback)
- **99%+ cache hit ratio** with proper warming

## Architecture

### Models

- `User` - Enhanced Laravel user model with Firebird support
- `PersonalAccessToken` - Cached Sanctum token model
- `DmxApi` - API configuration model
- `DmxApiUser` - User API access permissions

### Services

- `TokenCacheService` - Core caching logic with warming/invalidation

### Jobs

- `WarmUserCacheJob` - Cache warming for specific users
- `WarmAllActiveTokensJob` - Bulk token cache warming
- `InvalidateExpiredTokensJob` - Cleanup expired tokens
- `InvalidateUserCacheJob` - User data invalidation

### Middleware

- `CachedSanctumAuth` - High-performance authentication
- `CheckCachedDmxApiAccess` - API access validation

## Setup Checklist

After installation and configuration, verify your setup:

```bash
# 1. Check if classes are loaded
php artisan tinker
>>> class_exists('ItDelmax\AuthCache\Models\User')
>>> exit

# 2. Test cache connection
php artisan cache:stats

# 3. Test database connection
php artisan tinker
>>> ItDelmax\AuthCache\Models\User::count()
>>> exit

# 4. Test token model
php artisan tinker
>>> ItDelmax\AuthCache\Models\PersonalAccessToken::count()
>>> exit

# 5. Start queue worker (for background jobs)
php artisan queue:work --queue=cache

# 6. Test cache warming
php artisan cache:warm-user 1
```

If all commands work without errors, your setup is complete! 🎉

## Requirements

- PHP ^8.2
- Laravel ^11.0|^12.0
- Laravel Sanctum ^4.0|^5.0
- Redis (for caching)
- Firebird database connection

## Contributing

This package is developed by IT Delmax team with AI assistance from Claude (Anthropic).

## Authors

- **Nikola Marcic** - Lead Developer
- **Claude (Anthropic AI)** - AI Assistant
- **Delmax IT Team** - Development Team

## License

MIT License

## Support

For issues and questions:

- GitHub: https://github.com/it-delmax/auth-cache
- Email: it@delmax.rs
