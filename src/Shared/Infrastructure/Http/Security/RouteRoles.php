<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Security;

final class RouteRoles
{
    // Routes that needs an admin role
    private const ADMIN_ROUTES = [
        ['method' => 'PUT', 'path' => '/api/v1/users', 'suffix' => '/roles'],
        ['method' => 'DELETE', 'path' => '/api/v1/users'],
    ];

    /**
     * @return array<int,string>
     */
    public static function requiredRoles(string $method, string $path): array
    {
        foreach (self::ADMIN_ROUTES as $route) {
            if (
                $route['method'] === strtoupper($method)
                && str_starts_with($path, $route['path'])
            ) {
                return ['ROLE_ADMIN'];
            }
        }

        return ['ROLE_USER'];
    }

    public static function requiresAdmin(string $method, string $path): bool
    {
        return in_array('ROLE_ADMIN', self::requiredRoles($method, $path), true);
    }
}
