<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Exception\InvalidTokenException;
use App\User\Domain\Exception\TokenExpiredException;
use App\User\Domain\Service\TokenServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException(
                'invalid_token',
            );
        }

        $token = trim(substr($authHeader, 7));

        try {
            $claims = $this->tokenService->decodeAccessToken($token);
        } catch (TokenExpiredException) {
            throw new CustomUserMessageAuthenticationException('token_expired');
        } catch (InvalidTokenException) {
            throw new CustomUserMessageAuthenticationException('invalid_token');
        }

        return new SelfValidatingPassport(
            new UserBadge($claims->email),
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName,
    ): ?Response {
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception,
    ): ?Response {
        $errorCode = $exception->getMessage();

        return new JsonResponse([
            'error' => [
                'code' => $errorCode,
                'message' => match ($errorCode) {
                    'token_expired' => 'The access token has expired.',
                    'invalid_token' => 'The access token is invalid.',
                    default => 'Authentication failed.',
                },
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
