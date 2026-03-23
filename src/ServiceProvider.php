<?php

declare(strict_types=1);

namespace SaeedHosan\Tenancy;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use SaeedHosan\Tenancy\Contracts\TenantResolver;
use SaeedHosan\Tenancy\Exceptions\TenancyException;
use SaeedHosan\Tenancy\Http\Middleware\TenantMiddleware;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // config
        $this->mergeConfigFrom(__DIR__.'/../config/tenancy.php', 'tenancy');

        // binding
        $this->app->singleton(TenantContext::class);
        $this->app->bind(TenantResolver::class, function (): TenantResolver {

            $resolver = config('tenancy.resolver');

            if (! is_string($resolver) || ! class_exists($resolver)) {
                throw TenancyException::invalidResolverValue($resolver);
            }

            $instance = app($resolver);

            if (! $instance instanceof TenantResolver) {
                throw TenancyException::resolverMustImplement($resolver, TenantResolver::class);
            }

            return $instance;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerMiddlewareAlias();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tenancy.php' => config_path('tenancy.php'),
            ], 'tenancy-config');
        }
    }

    /**
     * Register the tenant middleware alias.
     */
    protected function registerMiddlewareAlias(): void
    {
        Route::aliasMiddleware('tenant', TenantMiddleware::class);
    }
}
