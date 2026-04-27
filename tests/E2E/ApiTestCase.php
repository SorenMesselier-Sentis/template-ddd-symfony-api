<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']);
    }

    protected function get(string $uri, array $params = []): array
    {
        $url = $params ? $uri.'?'.http_build_query($params) : $uri;
        $this->client->request('GET', $url);

        return $this->json();
    }

    protected function post(string $uri, array $body = []): array
    {
        $this->client->request('POST', $uri, [], [], [], json_encode($body));

        return $this->json();
    }

    protected function patch(string $uri, array $body = []): array
    {
        $this->client->request('PATCH', $uri, [], [], [], json_encode($body));

        return $this->json();
    }

    protected function put(string $uri, array $body = []): array
    {
        $this->client->request('PUT', $uri, [], [], [], json_encode($body));

        return $this->json();
    }

    protected function delete(string $uri): void
    {
        $this->client->request('DELETE', $uri);
    }

    protected function json(): array
    {
        $content = $this->client->getResponse()->getContent();

        return json_decode($content, true) ?? [];
    }

    protected function statusCode(): int
    {
        return $this->client->getResponse()->getStatusCode();
    }
}
