<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site-wide HTTP Basic Auth
    |--------------------------------------------------------------------------
    |
    | Keeps staging/demo (or a soft-launch site) behind a single shared
    | credential. Disabled by default; turn it on per environment in the ECS
    | task definition. If enabled but the username or password is empty, the
    | site stays open rather than locking everyone out.
    |
    */

    'enabled' => (bool) env('BASIC_AUTH_ENABLED', false),

    'username' => (string) env('BASIC_AUTH_USERNAME', ''),

    'password' => (string) env('BASIC_AUTH_PASSWORD', ''),

    'realm' => (string) env('BASIC_AUTH_REALM', 'Restricted'),

    /*
    |--------------------------------------------------------------------------
    | Middleware groups
    |--------------------------------------------------------------------------
    |
    | Groups the middleware is prepended to. Groups that don't exist are
    | skipped, so `statamic.cp` is harmless on non-Statamic apps. Routes
    | outside these groups (e.g. Laravel's `/up` health check) are not
    | protected.
    |
    */

    'middleware_groups' => ['web', 'statamic.cp'],

    /*
    |--------------------------------------------------------------------------
    | Excluded paths
    |--------------------------------------------------------------------------
    |
    | Path patterns (as accepted by `$request->is()`) that skip basic auth,
    | e.g. inbound webhooks: ['webhooks/*', 'payments/callback'].
    |
    */

    'except' => [],

];
