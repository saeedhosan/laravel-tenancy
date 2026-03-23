<?php

declare(strict_types=1);

namespace SaeedHosan\Tenancy\Exceptions;

use RuntimeException;

class TenancyException extends RuntimeException
{
    public static function invalidResolverValue(mixed $resolver): self
    {
        $type = get_debug_type($resolver);

        return new self("Invalid tenancy.resolver config. Expected non-empty class-string, got {$type}.");
    }

    public static function resolverMustImplement(string $resolver, string $contract): self
    {
        return new self(sprintf(
            'The tenancy resolver [%s] must implement %s.',
            $resolver,
            $contract
        ));
    }
}
