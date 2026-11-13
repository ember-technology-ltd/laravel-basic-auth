<?php

namespace Ember\BasicAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnforceBasicAuth
{
    /**
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! filter_var(config('basic_auth.enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        $except = array_values(array_filter((array) config('basic_auth.except', [])));

        if ($except !== [] && $request->is(...$except)) {
            return $next($request);
        }

        $expectedUser = (string) config('basic_auth.username', '');
        $expectedPass = (string) config('basic_auth.password', '');

        // Fail-safe: if credentials are missing, don't lock the site out.
        if ($expectedUser === '' || $expectedPass === '') {
            return $next($request);
        }

        // Evaluate both comparisons (no short-circuit) so timing doesn't
        // reveal whether the username was correct.
        $userOk = hash_equals($expectedUser, (string) $request->getUser());
        $passOk = hash_equals($expectedPass, (string) $request->getPassword());

        if ($userOk & $passOk) {
            return $next($request);
        }

        return new Response('Authentication required.', 401, [
            'WWW-Authenticate' => sprintf('Basic realm="%s", charset="UTF-8"', $this->realm()),
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function realm(): string
    {
        // Strip anything that could break out of the quoted header value.
        $realm = preg_replace('/["\\\\\x00-\x1F\x7F]/', '', (string) config('basic_auth.realm', 'Restricted'));

        return $realm === '' ? 'Restricted' : $realm;
    }
}
