<?php

namespace ItDelmax\AuthCache\Passwords;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use ItDelmax\AuthCache\Passwords\CustomPasswordBroker;

class CustomPasswordBrokerManager extends PasswordBrokerManager implements PasswordBrokerFactory
{
  protected function resolve($name): PasswordBroker
  {
    $config = $this->getConfig($name);

    return new CustomPasswordBroker(
      $this->createTokenRepository($config),
      $this->app['auth']->createUserProvider($config['provider'])
    );
  }
}
