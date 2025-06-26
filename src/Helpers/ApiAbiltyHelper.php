<?php

namespace ItDelmax\AuthCache\Helpers;

class ApiAbilityHelper
{
  /**
   * Proverava da li abilities JSON sadrži određeni API i permission.
   *
   * @param array|string|null $abilities JSON string ili dekodiran niz
   * @param string $slug npr. 'stock-api'
   * @param string $ability npr. 'stock:view'
   * @return bool
   */
  public static function has(string|array|null $abilities, string $slug, string $ability): bool
  {
    if (is_string($abilities)) {
      $abilities = json_decode($abilities, true);
    }

    if (!is_array($abilities) || !isset($abilities[$slug])) {
      return false;
    }

    return in_array($ability, $abilities[$slug]);
  }
}
