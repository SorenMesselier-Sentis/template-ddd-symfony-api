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
        $this->client = static::createClient();
    }

    protected function get(string $uri, array $params = []): array
    {
        $url = $params ? $uri.'?'.http_build_query($params) : $uri;

        $this->client->request(
            method:  'GET',
            uri:     $url,
            server:  $this->headers(),
        );

        return $this->json();
    }

    protected function post(string $uri, array $body = []): array
    {
        $this->client->request(
            method:  'POST',
            uri:     $uri,
            server:  $this->headers(),
            content: json_encode($body),
        );

        return $this->json();
    }

    protected function patch(string $uri, array $body = []): array
    {
        $this->client->request(
            method:  'PATCH',
            uri:     $uri,
            server:  $this->headers(),
            content: json_encode($body),
        );

        return $this->json();
    }

    protected function put(string $uri, array $body = []): array
    {
        $this->client->request(
            method:  'PUT',
            uri:     $uri,
            server:  $this->headers(),
            content: json_encode($body),
        );

        return $this->json();
    }

    protected function delete(string $uri): void
    {
        $this->client->request(
            method: 'DELETE',
            uri:    $uri,
            server: $this->headers(),
        );
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

    protected function assertStatus(int $expected): void
    {
        $this->assertResponseStatusCodeSame($expected);
    }

    private function headers(): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT'  => 'application/json',
        ];
    }
}
