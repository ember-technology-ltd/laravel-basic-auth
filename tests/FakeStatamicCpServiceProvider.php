<?php

namespace Ember\BasicAuth\Tests;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/**
 * Stands in for Statamic's CpServiceProvider, which registers the
 * `statamic.cp` middleware group in its boot() method. Registered *before*
 * the package provider in the tests, but a real app's order varies.
 */
class FakeStatamicCpServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(Router::class)->middlewareGroup('statamic.cp', []);
    }
}
