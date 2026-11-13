<?php

namespace Ember\BasicAuth;

use Ember\BasicAuth\Http\Middleware\EnforceBasicAuth;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class BasicAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/basic_auth.php', 'basic_auth');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/basic_auth.php' => $this->app->configPath('basic_auth.php'),
            ], 'basic-auth-config');
        }

        $this->app->make(Router::class)->aliasMiddleware('basic-auth', EnforceBasicAuth::class);

        // Apply once every provider has booted, so groups registered by other
        // packages (e.g. Statamic's `statamic.cp`) exist by then.
        $this->app->booted(function () {
            $this->prependToGroups();
        });

        // The HTTP kernel overwrites the router's groups with its own when it
        // is resolved (Http\Kernel in Laravel 8–10, bootstrap/app.php in 11+).
        // In a web request that happens before boot, but tests and some
        // runtimes resolve it later, so re-apply afterwards.
        $this->app->afterResolving(HttpKernel::class, function () {
            if ($this->app->isBooted()) {
                $this->prependToGroups();
            }
        });
    }

    private function prependToGroups(): void
    {
        $router = $this->app->make(Router::class);

        foreach ((array) config('basic_auth.middleware_groups', []) as $group) {
            // prependMiddlewareToGroup() ignores duplicates, so this is safe
            // to call more than once.
            if ($router->hasMiddlewareGroup($group)) {
                $router->prependMiddlewareToGroup($group, EnforceBasicAuth::class);
            }
        }
    }
}
