<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Http\Controller;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Deliberately RFC 6749-compliant (`access_token`/`token_type`/`expires_in`/`scope`, and
 * `error`/`error_description` on failure) instead of the app's usual ApiResponse envelope —
 * standard OAuth2 clients (and tooling) expect exactly this shape from a token endpoint. See
 * docs/api-clients.md.
 */
#[Route('/oauth/token', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/oauth/token',
    operationId: 'postOauthToken',
    summary: 'Issue a machine-to-machine access token (OAuth2 client_credentials)',
    description: 'RFC 6749 token endpoint — request body is `application/x-www-form-urlencoded` with `grant_type=client_credentials`, `client_id`, `client_secret`, and an optional `scope`.',
    tags: ['ApiClients'],
)]
#[OA\RequestBody(
    required: true,
    content: new OA\MediaType(
        mediaType: 'application/x-www-form-urlencoded',
        schema: new OA\Schema(
            required: ['grant_type', 'client_id', 'client_secret'],
            properties: [
                new OA\Property(property: 'grant_type', type: 'string', enum: ['client_credentials']),
                new OA\Property(property: 'client_id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'client_secret', type: 'string'),
                new OA\Property(property: 'scope', type: 'string', description: 'Space-separated scope list; defaults to every scope granted to the client.'),
            ],
        ),
    ),
)]
#[OA\Response(
    response: 200,
    description: 'Access token issued',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'access_token', type: 'string'),
            new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
            new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
            new OA\Property(property: 'scope', type: 'string'),
        ],
    ),
)]
#[OA\Response(response: 400, description: 'Invalid or unsupported grant (RFC 6749 error body: `error`/`error_description`)')]
#[OA\Response(response: 401, description: 'Invalid client credentials (RFC 6749 error body)')]
final class IssueAccessTokenController
{
    public function __construct(
        private readonly AuthorizationServer $authorizationServer,
        private readonly HttpMessageFactoryInterface $psrHttpFactory,
        private readonly HttpFoundationFactoryInterface $httpFoundationFactory,
        private readonly ResponseFactoryInterface $psrResponseFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $psrResponse = $this->authorizationServer->respondToAccessTokenRequest(
                $this->psrHttpFactory->createRequest($request),
                $this->psrResponseFactory->createResponse(),
            );
        } catch (OAuthServerException $exception) {
            $psrResponse = $exception->generateHttpResponse($this->psrResponseFactory->createResponse());
        }

        return $this->httpFoundationFactory->createResponse($psrResponse);
    }
}
