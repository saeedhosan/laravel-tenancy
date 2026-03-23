<?php

declare(strict_types=1);

use SaeedHosan\Tenancy\TenantContext;

it('returns tenant context via currentTenant() helper', function (): void {
    $context = currentTenant();

    expect($context)->toBeInstanceOf(TenantContext::class);
});

it('returns same instance via currentTenant() helper', function (): void {
    $first = currentTenant();
    $second = currentTenant();

    expect($first)->toBe($second);
});
