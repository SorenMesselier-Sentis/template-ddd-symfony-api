<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Shared\Infrastructure\Fixture\FixtureData;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class HttpTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
    }

    protected function createAuthenticatedClient(string $role = 'user'): KernelBrowser
    {
        $client = static::createClient();
        $this->resetDatabase();

        $credentials = 'admin' === $role
            ? ['email' => FixtureData::USER_JOHN_EMAIL, 'password' => FixtureData::DEFAULT_PASSWORD]
            : ['email' => FixtureData::USER_JANE_EMAIL, 'password' => FixtureData::DEFAULT_PASSWORD];

        $client->request(
            'POST',
            '/api/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($credentials, JSON_THROW_ON_ERROR),
        );

        $response = $client->getResponse();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (200 !== $response->getStatusCode()) {
            $message = $payload['error']['message'] ?? $response->getContent();
            self::fail(sprintf('Login failed with status %d: %s', $response->getStatusCode(), $message));
        }

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$payload['data']['access_token']);

        return $client;
    }

    protected function resetDatabase(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $purger = new ORMPurger($em);
        $purger->purge();
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($container->get('doctrine.fixtures.loader')->getFixtures());

        $this->resetFeatureFlags($em->getConnection());
    }

    /**
     * feature_flags is plain DBAL (see DoctrineFeatureFlagRepository), not an
     * ORM-managed entity, so ORMPurger never touches it — without this, a test
     * that toggles a flag (e.g. disabling cursor_pagination) would leak that
     * state into every other test in the suite.
     */
    private function resetFeatureFlags(Connection $connection): void
    {
        $connection->executeStatement('TRUNCATE TABLE feature_flags');
        $connection->executeStatement(
            "INSERT INTO feature_flags (flag_key, enabled, description, updated_at) VALUES ('cursor_pagination', true, 'Keyset (cursor) pagination on GET /users and GET /documents.', now())",
        );
    }

    protected function assertJsonEnvelope(Response $response, int $expectedStatus): void
    {
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('data', $payload);
    }
}
