<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Security;

final class PublicRoutes
{
    private const ROUTES = [
        ['method' => 'POST', 'path' => '/api/v1/auth/login'],
        ['method' => 'POST', 'path' => '/api/v1/auth/refresh'],
        ['method' => 'GET',  'path' => '/api/docs'],
        ['method' => 'GET',  'path' => '/api/v1/docs'],
    ];

    public static function isPublic(string $method, string $path): bool
    {
        foreach (self::ROUTES as $route) {
            if (
                $route['method'] === strtoupper($method)
                && str_starts_with($path, $route['path'])
            ) {
                return true;
            }
        }

        return false;
    }
}
