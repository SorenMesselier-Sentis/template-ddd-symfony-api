<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class JsonAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(
            data: [
                'error' => [
                    'code' => 'authentication.missing_token',
                    'message' => 'Authorization token is missing.',
                ],
            ],
            status: Response::HTTP_UNAUTHORIZED,
        );
    }
}
