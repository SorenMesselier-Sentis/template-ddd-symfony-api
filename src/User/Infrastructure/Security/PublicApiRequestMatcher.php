<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Matches routes declared as PUBLIC_ACCESS in config/packages/security.yaml.
 */
final class PublicApiRequestMatcher
{
    public function matches(Request $request): bool
    {
        $path = $request->getPathInfo();

        if (1 === preg_match('#^/api/v1/auth/(login|refresh)$#', $path)) {
            return true;
        }

        return str_starts_with($path, '/api/doc');
    }
}
