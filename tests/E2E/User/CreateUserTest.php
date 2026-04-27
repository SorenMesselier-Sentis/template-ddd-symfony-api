<?php


declare(strict_types=1);

namespace App\Tests\E2E\User;

use App\Tests\E2E\ApiTestCase;

final class CreateUserTest extends ApiTestCase
{
    public function test_it_creates_a_user(): void
    {
        $response = $this->post('/api/v1/users', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'secret1234',
        ]);

        $this->assertEquals(201, $this->statusCode());
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('id', $response['data']);
    }

    public function test_it_returns_409_when_email_already_exists(): void
    {
        $body = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'duplicate@example.com',
            'password' => 'secret1234',
        ];

        $this->post('/api/v1/users', $body);
        $this->post('/api/v1/users', $body);

        $this->assertEquals(409, $this->statusCode());
    }

    public function test_it_returns_400_when_fields_are_missing(): void
    {
        $this->post('/api/v1/users', ['firstName' => 'John']);

        $this->assertEquals(400, $this->statusCode());
    }
}
