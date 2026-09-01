<?php

declare(strict_types=1);

namespace App\Tests\Http\ApiClient;

use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Tests\Http\HttpTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class IssueAccessTokenControllerTest extends HttpTestCase
{
    public function testItIssuesAnAccessTokenForValidClientCredentials(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $client->request('POST', '/api/v1/oauth/token', parameters: [
            'grant_type' => 'client_credentials',
            'client_id' => FixtureData::API_CLIENT_TEST_ID,
            'client_secret' => FixtureData::API_CLIENT_TEST_SECRET,
        ]);

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('access_token', $payload);
        $this->assertSame('Bearer', $payload['token_type']);
        $this->assertGreaterThan(0, $payload['expires_in']);
    }

    public function testItRejectsAWrongClientSecret(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $client->request('POST', '/api/v1/oauth/token', parameters: [
            'grant_type' => 'client_credentials',
            'client_id' => FixtureData::API_CLIENT_TEST_ID,
            'client_secret' => 'not-the-right-secret',
        ]);

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('invalid_client', $payload['error']);
    }

    public function testItRejectsAnUnknownClientId(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $client->request('POST', '/api/v1/oauth/token', parameters: [
            'grant_type' => 'client_credentials',
            'client_id' => '00000000-0000-4000-8000-000000000000',
            'client_secret' => 'whatever',
        ]);

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testItRejectsAnUnsupportedGrantType(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $client->request('POST', '/api/v1/oauth/token', parameters: [
            'grant_type' => 'authorization_code',
            'client_id' => FixtureData::API_CLIENT_TEST_ID,
            'client_secret' => FixtureData::API_CLIENT_TEST_SECRET,
        ]);

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('unsupported_grant_type', $payload['error']);
    }

    /**
     * The generic authorizer refactor (PrincipalRoleAuthorizer) is the crux of this feature:
     * an OAuth2 client must authenticate successfully (no 401) on a protected route it isn't
     * scoped for, and be turned away by role/scope, not by a broken "who is the caller" check.
     */
    public function testAValidClientTokenIsRejectedByRoleNotByAuthentication(): void
    {
        $client = static::createClient();
        $this->resetDatabase();
        $accessToken = $this->issueTestClientToken($client);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$accessToken);
        $client->request('GET', '/api/v1/projects');

        $response = $client->getResponse();
        $this->assertSame(403, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('insufficient_privileges', $payload['error']['code']);
    }

    public function testARevokedClientsTokenIsRejected(): void
    {
        // Single client instance throughout — WebTestCase only allows one kernel boot
        // (one createClient() call) per test; switch the Authorization header between requests
        // instead of creating a second browser.
        $client = $this->createAuthenticatedClient('admin');
        $accessToken = $this->issueTestClientToken($client);

        $client->request('POST', sprintf('/api/v1/api-clients/%s/revoke', FixtureData::API_CLIENT_TEST_ID));
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$accessToken);
        $client->request('GET', '/api/v1/projects');

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    private function issueTestClientToken(KernelBrowser $client): string
    {
        $client->request('POST', '/api/v1/oauth/token', parameters: [
            'grant_type' => 'client_credentials',
            'client_id' => FixtureData::API_CLIENT_TEST_ID,
            'client_secret' => FixtureData::API_CLIENT_TEST_SECRET,
        ]);

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $payload['access_token'];
    }
}
