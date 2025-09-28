<?php

namespace ItDelmax\AuthCache\Passwords;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;

class DelmaxPasswordBrokerManager extends PasswordBrokerManager implements PasswordBrokerFactory
{
  protected function createTokenRepository(array $config)
  {
    $key        = $this->app['config']['app.key'];
    $connection = $this->app['db']->connection($config['connection'] ?? null);

    return new DelmaxDatabaseTokenRepository(
      $connection,
      $this->app['hash'],
      $config['table'],
      $key,
      $config['expire'],
      $config['throttle'] ?? 0,
      $config['prune'] ?? false
    );
  }

  /**
   * (nije obavezno) Ako želiš i broker da bude custom, možeš ovo zadržati ili proširiti.
   */
  public function broker($name = null): PasswordBroker
  {
    return parent::broker($name);
  }
}
