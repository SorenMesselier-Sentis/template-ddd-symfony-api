<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Tests\Http\HttpTestCase;

final class EraseMyDataControllerTest extends HttpTestCase
{
    public function testItErasesTheCallersOwnDataAndBlocksFurtherLogin(): void
    {
        // Single client instance throughout — WebTestCase only allows one kernel boot
        // (one createClient() call) per test; /auth/login is PUBLIC_ACCESS and never even
        // attempts to authenticate the stale Authorization header still set on $client (see
        // PublicApiRequestMatcher), so reusing it here is safe.
        $client = $this->createAuthenticatedClient('user');

        $client->request('DELETE', '/api/v1/users/me');
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->request(
            'POST',
            '/api/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => FixtureData::USER_JANE_EMAIL, 'password' => FixtureData::DEFAULT_PASSWORD], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testItAnonymizesTheProfileRow(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request('DELETE', '/api/v1/users/me');
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $connection = static::getContainer()->get('doctrine')->getConnection();
        $row = $connection->fetchAssociative('SELECT * FROM users WHERE id = ?', [FixtureData::USER_JANE_ID]);

        $this->assertNotFalse($row);
        $this->assertSame('deleted', $row['first_name']);
        $this->assertSame('user', $row['last_name']);
        $this->assertNotSame(FixtureData::USER_JANE_EMAIL, $row['email']);
        $this->assertSame('deleted', $row['status']);
    }

    public function testItPermanentlyDeletesOwnedDocuments(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $connection = static::getContainer()->get('doctrine')->getConnection();
        $before = $connection->fetchOne('SELECT COUNT(*) FROM documents WHERE id = ?', [FixtureData::DOCUMENT_JANE_CONTRACT_ID]);
        $this->assertSame(1, (int) $before);

        $client->request('DELETE', '/api/v1/users/me');
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $after = $connection->fetchOne('SELECT COUNT(*) FROM documents WHERE id = ?', [FixtureData::DOCUMENT_JANE_CONTRACT_ID]);
        $this->assertSame(0, (int) $after);
    }

    public function testItRevokesRefreshTokens(): void
    {
        $client = $this->createAuthenticatedClient('user');

        // A second login for the same user, to capture its own refresh token (createAuthenticatedClient's
        // is discarded). See LoginUserControllerTest for the regression test covering this no
        // longer colliding with the first login's refresh token.
        $client->request(
            'POST',
            '/api/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => FixtureData::USER_JANE_EMAIL, 'password' => FixtureData::DEFAULT_PASSWORD], JSON_THROW_ON_ERROR),
        );
        $loginPayload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $refreshToken = $loginPayload['data']['refresh_token'];

        $client->request('DELETE', '/api/v1/users/me');
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $client->request(
            'POST',
            '/api/v1/auth/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['refresh_token' => $refreshToken], JSON_THROW_ON_ERROR),
        );
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testErasureIsAudited(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request('DELETE', '/api/v1/users/me');
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $connection = static::getContainer()->get('doctrine')->getConnection();
        $row = $connection->fetchAssociative(
            'SELECT * FROM audit_log WHERE action = ? AND target_id = ?',
            ['user.data_erased', FixtureData::USER_JANE_ID],
        );

        $this->assertNotFalse($row);
    }

    public function testAdminDeletingAUserAlsoErasesTheirData(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request('DELETE', sprintf('/api/v1/users/%s', FixtureData::USER_BOB_ID));
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        $connection = static::getContainer()->get('doctrine')->getConnection();
        $row = $connection->fetchAssociative('SELECT * FROM users WHERE id = ?', [FixtureData::USER_BOB_ID]);

        $this->assertNotFalse($row);
        $this->assertSame('deleted', $row['first_name']);
        $this->assertNotSame(FixtureData::USER_BOB_EMAIL, $row['email']);
    }
}
