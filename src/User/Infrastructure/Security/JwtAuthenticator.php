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
        private readonly PublicApiRequestMatcher $publicApiRequestMatcher,
    ) {
    }

    /**
     * The `api` firewall also authenticates OAuth2 client_credentials Bearer tokens (see
     * ApiClient\Infrastructure\Security\OAuth2ClientAuthenticator on the same firewall) — both
     * share the same `^/api` pattern and Bearer scheme, so this must not blindly claim every
     * non-public request. league/oauth2-server always sets a `scopes` claim and never an
     * `email` one; the Lexik access token here is the exact opposite (see JwtTokenService).
     * Peeking at the unverified payload is just cheap routing — the real, signature-verified
     * check still happens in authenticate() below.
     */
    public function supports(Request $request): bool
    {
        if ($this->publicApiRequestMatcher->matches($request)) {
            return false;
        }

        $token = $this->bearerToken($request);

        return null === $token || !self::looksLikeOAuth2Token($token);
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->bearerToken($request);

        if (null === $token) {
            throw new CustomUserMessageAuthenticationException('authentication.missing_token');
        }

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
    ): Response {
        $errorCode = $exception->getMessage();

        return new JsonResponse([
            'error' => [
                'code' => $errorCode,
                'message' => match ($errorCode) {
                    'authentication.missing_token' => 'Authorization token is missing.',
                    'token_expired' => 'The access token has expired.',
                    'invalid_token' => 'The access token is invalid.',
                    default => 'Authentication failed.',
                },
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function bearerToken(Request $request): ?string
    {
        if (!$request->headers->has('Authorization')) {
            return null;
        }

        $authHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return trim(substr($authHeader, 7));
    }

    private static function looksLikeOAuth2Token(string $token): bool
    {
        $parts = explode('.', $token);

        if (3 !== \count($parts)) {
            return false;
        }

        $payload = json_decode(self::base64UrlDecode($parts[1]), true);

        if (!\is_array($payload)) {
            return false;
        }

        return isset($payload['scopes']) && !isset($payload['email']);
    }

    private static function base64UrlDecode(string $data): string
    {
        $base64 = strtr($data, '-_', '+/');
        $padded = str_pad($base64, \strlen($base64) + (4 - \strlen($base64) % 4) % 4, '=');
        $decoded = base64_decode($padded, true);

        return false !== $decoded ? $decoded : '';
    }
}
