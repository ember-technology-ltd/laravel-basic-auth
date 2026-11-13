# laravel-basic-auth

Site-wide HTTP basic auth for Laravel and Statamic apps, switched on per environment with env vars. Intended for staging, demo and soft-launch sites.

- Laravel 8–13, PHP 8.2+
- No code changes in the app: install it and set env vars
- Protects the `web` middleware group and Statamic's control panel (`statamic.cp`)
- Leaves Laravel's `/up` health check open (it sits outside both groups)
- Timing-safe credential check; fails open if enabled without credentials

## Install

Add the repository to the app's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:ember-technology-ltd/laravel-basic-auth.git"
    }
]
```

Then:

```bash
composer require ember-technology-ltd/laravel-basic-auth
```

The service provider is auto-discovered.

## Configure

| Env var | Default | |
|---|---|---|
| `BASIC_AUTH_ENABLED` | `false` | Turn auth on |
| `BASIC_AUTH_USERNAME` | | |
| `BASIC_AUTH_PASSWORD` | | Inject from Secrets Manager/SSM in ECS |
| `BASIC_AUTH_REALM` | `Restricted` | Shown in some browsers' login prompt |

If `BASIC_AUTH_ENABLED=true` but the username or password is empty, the site stays open rather than locking everyone out.

Keep it disabled in `phpunit.xml`:

```xml
<env name="BASIC_AUTH_ENABLED" value="false" force="true"/>
```

### Excluding paths or adding groups

Publish the config:

```bash
php artisan vendor:publish --tag=basic-auth-config
```

- `except`: path patterns that skip auth, e.g. `['webhooks/*']`
- `middleware_groups`: groups to protect (default `['web', 'statamic.cp']`; missing groups are ignored)

The middleware is also available as the `basic-auth` alias for individual routes.

## Caveats

- **Routes outside the groups are not protected**, e.g. `/up`, `routes/api.php`, Statamic's REST/GraphQL API and Glide image routes.
- **Statamic full static caching** (`file` driver served directly by nginx) bypasses PHP, so cached pages skip auth. Half-measure static caching is fine.
- **CDNs**: check that an unauthenticated request returns 401 rather than a cached page. CloudFront includes `Authorization` in the cache key when it forwards it; the 401 response is sent with `Cache-Control: no-store`.

## Migrating an app with its own `EnforceBasicAuth`

1. Remove the app's middleware class, its registration (`bootstrap/app.php` or `app/Http/Kernel.php`) and its tests.
2. Keep or delete `config/basic_auth.php`. The package uses the same config key and env vars, so an existing published file keeps working (add `middleware_groups` and `except` to it if you need them).

## Development

```bash
composer install
vendor/bin/phpunit
```

CI runs the suite against every supported Laravel major.
