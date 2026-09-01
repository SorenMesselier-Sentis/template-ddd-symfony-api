<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Security;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
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

/**
 * Authenticates OAuth2 client_credentials Bearer tokens on the same `api` firewall as the
 * human-user JwtAuthenticator (Lexik). Both share the same `^/api` pattern and Bearer scheme,
 * so `supports()` distinguishes them cheaply, before any cryptographic check, by peeking at the
 * (unverified) JWT payload: league always sets a `scopes` claim and never an `email` claim,
 * the Lexik access token is the exact opposite (see JwtTokenService) — a non-OAuth2 or garbage
 * token simply falls through with supports() = false, and either authenticator that DOES claim
 * it will still reject it during the real, signature-verified authenticate() step below.
 */
final class OAuth2ClientAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ResourceServer $resourceServer,
        private readonly HttpMessageFactoryInterface $psrHttpFactory,
    ) {
    }

    public function supports(Request $request): bool
    {
        $token = $this->bearerToken($request);

        return null !== $token && self::looksLikeOAuth2Token($token);
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->bearerToken($request);

        if (null === $token) {
            throw new CustomUserMessageAuthenticationException('authentication.missing_token');
        }

        try {
            $validated = $this->resourceServer->validateAuthenticatedRequest($this->psrHttpFactory->createRequest($request));
        } catch (OAuthServerException) {
            throw new CustomUserMessageAuthenticationException('invalid_token');
        }

        $clientId = $validated->getAttribute('oauth_client_id');
        $scopes = $validated->getAttribute('oauth_scopes');

        if (!\is_string($clientId) || '' === $clientId) {
            throw new CustomUserMessageAuthenticationException('invalid_token');
        }

        /** @var list<string> $scopes */
        $scopes = \is_array($scopes) ? array_values(array_filter($scopes, 'is_string')) : [];

        return new SelfValidatingPassport(
            new UserBadge($clientId, static fn () => new ApiClientSecurityAdapter($clientId, $scopes)),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $errorCode = $exception->getMessage();

        return new JsonResponse([
            'error' => [
                'code' => $errorCode,
                'message' => match ($errorCode) {
                    'authentication.missing_token' => 'Authorization token is missing.',
                    'invalid_token' => 'The access token is invalid, expired, or revoked.',
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
