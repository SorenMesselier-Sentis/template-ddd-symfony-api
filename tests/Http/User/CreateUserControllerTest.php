<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class CreateUserControllerTest extends HttpTestCase
{
    public function testCreateUserReturns201WithDataEnvelope(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request(
            'POST',
            '/api/v1/users',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'firstName' => 'Alice',
                'lastName' => 'Wonder',
                'email' => 'alice@example.com',
                'password' => 'secret1234',
            ], JSON_THROW_ON_ERROR),
        );

        $this->assertJsonEnvelope($client->getResponse(), 201);
    }

    public function testMissingRequiredFieldReturnsValidationError(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request(
            'POST',
            '/api/v1/users',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'bad-email'], JSON_THROW_ON_ERROR),
        );

        $response = $client->getResponse();
        $this->assertSame(422, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('validation_error', $payload['error']['code']);
        $this->assertNotEmpty($payload['error']['errors']);
    }

    public function testUnauthenticatedRequestReturns401(): void
    {
        $client = static::createClient();
        $this->resetDatabase();
        $client->request('GET', '/api/v1/users');

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(401, $client->getResponse()->getStatusCode());
        $this->assertSame('authentication.missing_token', $payload['error']['code']);
    }
}
