<?php

declare(strict_types=1);

namespace App\Tests\Http;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
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
            ? ['email' => 'john.doe@example.com', 'password' => 'secret1234']
            : ['email' => 'jane.doe@example.com', 'password' => 'secret1234'];

        $client->request(
            'POST',
            '/api/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($credentials, JSON_THROW_ON_ERROR),
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

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
    }

    protected function assertJsonEnvelope(Response $response, int $expectedStatus): void
    {
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('data', $payload);
    }
}
