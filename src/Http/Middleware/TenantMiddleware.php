<?php

declare(strict_types=1);

namespace SaeedHosan\Tenancy\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use SaeedHosan\Tenancy\Contracts\TenantResolver;
use SaeedHosan\Tenancy\TenantContext;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request and establish tenant context.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Closure(Request): (Response)  $next  The next middleware in the pipeline
     * @return Response The HTTP response
     *
     * @throws AuthorizationException When user tries to access unauthorized tenant
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Authenticatable|null $user */
        $user = $request->user();
        $context = resolve(TenantContext::class);
        $resolver = resolve(TenantResolver::class);

        if (! $resolver->shouldScope($user)) {

            $context->setScoped(false);
            $context->setTenant(null);
            $context->setTenantKeys([]);

            return $next($request);
        }

        $context->setScoped(true);

        $userTenant = $resolver->resolveUserTenant($user);
        $context->setTenantKeys($resolver->resolveAccessibleTenantKeys($user, $userTenant));

        // Check if route contains a tenant parameter and validate access
        $routeTenant = $resolver->resolveRouteTenant($request);

        if ($routeTenant instanceof Model) {
            $routeTenantKey = $routeTenant->getKey();

            if (is_int($routeTenantKey) || is_string($routeTenantKey)) {
                throw_if(! $context->isTenantAccessible($routeTenantKey), AuthorizationException::class, 'This tenant is not available for the current context.');
            } elseif (is_numeric($routeTenantKey)) {
                throw_if(! $context->isTenantAccessible((int) $routeTenantKey), AuthorizationException::class, 'This tenant is not available for the current context.');
            } elseif (is_scalar($routeTenantKey)) {
                throw_if(! $context->isTenantAccessible((string) $routeTenantKey), AuthorizationException::class, 'This tenant is not available for the current context.');
            }
        }

        // Set current tenant to route tenant (if present) or user's tenant
        $context->setTenant($routeTenant ?? $userTenant);

        return $next($request);
    }
}
