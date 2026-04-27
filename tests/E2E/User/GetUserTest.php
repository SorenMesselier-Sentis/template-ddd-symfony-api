<?php

declare(strict_types=1);

namespace App\Tests\E2E\User;

use App\Tests\E2E\ApiTestCase;

final class GetUserTest extends ApiTestCase
{
    public function test_it_gets_a_user(): void
    {
        $created = $this->post('/api/v1/users', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'get.user@example.com',
            'password' => 'secret1234',
        ]);

        $id = $created['data']['id'];
        $response = $this->get("/api/v1/users/{$id}");

        $this->assertEquals(200, $this->statusCode());
        $this->assertEquals($id, $response['data']['id']);
        $this->assertEquals('John', $response['data']['first_name']);
        $this->assertEquals('get.user@example.com', $response['data']['email']);
    }

    public function test_it_returns_404_when_user_not_found(): void
    {
        $this->get('/api/v1/users/00000000-0000-4000-8000-000000000000');

        $this->assertEquals(404, $this->statusCode());
    }
}
