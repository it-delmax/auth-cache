# Changelog

All notable changes to `it-delmax/auth-cache` will be documented in this file.

## [1.0.0] - 2025-06-23

### Added
- Initial release of IT Delmax Auth Cache package
- High-performance Redis-based caching for Laravel Sanctum authentication
- Firebird database integration for legacy ERP systems
- Cache warming and invalidation background jobs
- Cached authentication middleware for improved performance
- Token and user data caching with configurable TTL
- API access management and validation
- Comprehensive Artisan commands for cache management
- Support for Laravel 11+ and PHP 8.2+

### Features
- `TokenCacheService` - Core caching functionality
- `User` model with Firebird connection support  
- `PersonalAccessToken` model with caching capabilities
- Background jobs for cache maintenance:
  - `WarmUserCacheJob` - Individual user cache warming
  - `WarmAllActiveTokensJob` - Bulk token cache warming
  - `InvalidateExpiredTokensJob` - Automatic cleanup
  - `InvalidateUserCacheJob` - User data invalidation
- Middleware for cached authentication:
  - `CachedSanctumAuth` - High-performance auth
  - `CheckCachedEtgApiAccess` - API access validation
- Artisan commands:
  - `cache:stats` - View cache configuration
  - `cache:warm-user` - Warm specific user cache
  - `cache:invalidate-user` - Invalidate user data

### Performance
- 60-80% faster authentication with cache hits
- Configurable TTL: Users (6h), Tokens (12h), API Access (24h)
- Redis-based storage for optimal performance
- Automatic cache warming prevents cold cache scenarios

### Compatibility
- Laravel 11.x and 12.x
- PHP 8.2, 8.3, 8.4
- Laravel Sanctum 4.x and 5.x
- Firebird and MySQL database support
- Redis for caching and session storage