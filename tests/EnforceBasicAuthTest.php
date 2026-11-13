<?php

namespace Ember\BasicAuth\Tests;

use Ember\BasicAuth\Http\Middleware\EnforceBasicAuth;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class EnforceBasicAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/page', function () {
            return 'page';
        });
        Route::middleware('statamic.cp')->get('/cp/dashboard', function () {
            return 'cp';
        });
        Route::get('/up', function () {
            return 'healthy';
        });
        Route::middleware('web')->post('/webhooks/stripe', function () {
            return 'hook';
        });
    }

    public function test_it_is_prepended_to_the_web_and_cp_groups(): void
    {
        // A real app resolves the kernel in public/index.php; Testbench only
        // does so on the first request.
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        $groups = $this->app->make(Router::class)->getMiddlewareGroups();

        $this->assertSame(EnforceBasicAuth::class, $groups['web'][0]);
        $this->assertSame(EnforceBasicAuth::class, $groups['statamic.cp'][0]);
    }

    public function test_it_survives_the_http_kernel_being_resolved_after_boot(): void
    {
        $this->app->forgetInstance(\Illuminate\Contracts\Http\Kernel::class);
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        $groups = $this->app->make(Router::class)->getMiddlewareGroups();

        $this->assertSame(EnforceBasicAuth::class, $groups['web'][0]);
        $this->assertSame(1, count(array_keys($groups['web'], EnforceBasicAuth::class, true)));
    }

    public function test_it_returns_401_without_credentials(): void
    {
        $response = $this->get('/page');

        $response->assertStatus(401);
        $this->assertSame('Basic realm="Staging", charset="UTF-8"', $response->headers->get('WWW-Authenticate'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_it_returns_401_with_a_wrong_password(): void
    {
        $this->get('/page', $this->credentials('demo', 'nope'))->assertStatus(401);
    }

    public function test_it_returns_401_with_a_wrong_username(): void
    {
        $this->get('/page', $this->credentials('intruder', 'secret'))->assertStatus(401);
    }

    public function test_it_returns_401_with_an_empty_password(): void
    {
        $this->get('/page', $this->credentials('demo', ''))->assertStatus(401);
    }

    public function test_it_lets_valid_credentials_through(): void
    {
        $this->get('/page', $this->credentials('demo', 'secret'))->assertOk()->assertSee('page');
    }

    public function test_it_protects_the_statamic_control_panel(): void
    {
        $this->get('/cp/dashboard')->assertStatus(401);
        $this->get('/cp/dashboard', $this->credentials('demo', 'secret'))->assertOk();
    }

    public function test_it_does_not_protect_routes_outside_the_groups(): void
    {
        $this->get('/up')->assertOk()->assertSee('healthy');
    }

    public function test_it_lets_everything_through_when_disabled(): void
    {
        config()->set('basic_auth.enabled', false);

        $this->get('/page')->assertOk();
    }

    public function test_it_fails_open_when_credentials_are_missing(): void
    {
        config()->set('basic_auth.password', '');

        $this->get('/page')->assertOk();
    }

    public function test_excluded_paths_skip_auth(): void
    {
        $this->post('/webhooks/stripe')->assertStatus(401);

        config()->set('basic_auth.except', ['webhooks/*']);

        $this->post('/webhooks/stripe')->assertOk();
    }

    public function test_the_realm_cannot_break_out_of_the_header(): void
    {
        config()->set('basic_auth.realm', "evil\"realm\r\nX-Injected: 1");

        $header = $this->get('/page')->headers->get('WWW-Authenticate');

        $this->assertSame('Basic realm="evilrealmX-Injected: 1", charset="UTF-8"', $header);
    }
}
