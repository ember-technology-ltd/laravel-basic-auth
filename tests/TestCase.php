<?php

namespace Ember\BasicAuth\Tests;

use Ember\BasicAuth\BasicAuthServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            FakeStatamicCpServiceProvider::class,
            BasicAuthServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('basic_auth.enabled', true);
        $app['config']->set('basic_auth.username', 'demo');
        $app['config']->set('basic_auth.password', 'secret');
        $app['config']->set('basic_auth.realm', 'Staging');
    }

    protected function credentials(string $user, string $pass): array
    {
        return ['PHP_AUTH_USER' => $user, 'PHP_AUTH_PW' => $pass];
    }
}
