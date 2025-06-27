<?php
//'check.api.token.access' => \App\Http\Middleware\CheckApiTokenAccess::class,
namespace App\Http\Middleware;

use App\Models\DmxApiAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class NoviCheckApiTokenAccess
{
  public function handle(Request $request, Closure $next, ...$requiredAbilities)
  {
    $authHeader = $request->bearerToken();

    if (!$authHeader) {
      throw new UnauthorizedHttpException('Bearer', 'Missing API token');
    }

    $token = DmxApiAccessToken::where('token', hash('sha256', $authHeader))->first();

    if (!$token || !$token->isValid()) {
      throw new UnauthorizedHttpException('Bearer', 'Invalid or expired token');
    }

    $abilities = $token->abilities ?? [];

    // API slug detekcija (možeš ovo zameniti sa konkretnom vrednošću)
    $slug = $request->route('slug') ?? $request->header('X-Api-Slug');

    if (!$slug || !isset($abilities[$slug])) {
      throw new UnauthorizedHttpException('Bearer', 'Token nema pristup za ovaj API');
    }

    foreach ($requiredAbilities as $ability) {
      if (!in_array($ability, $abilities[$slug])) {
        throw new UnauthorizedHttpException('Bearer', "Missing ability: {$ability} for API: {$slug}");
      }
    }

    $token->forceFill([
      'last_used_at' => now(),
      'last_ip_address' => $request->ip(),
    ])->save();

    $request->attributes->set('access_token', $token);

    return $next($request);
  }
}
