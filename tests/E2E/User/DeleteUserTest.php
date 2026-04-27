<?php

declare(strict_types=1);

namespace App\Tests\E2E\User;

use App\Tests\E2E\ApiTestCase;

final class DeleteUserTest extends ApiTestCase
{
    public function test_it_deletes_a_user(): void
    {
        $created = $this->post('/api/v1/users', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'delete.me@example.com',
            'password' => 'secret1234',
        ]);

        $id = $created['data']['id'];

        $this->delete("/api/v1/users/{$id}");
        $this->assertEquals(204, $this->statusCode());

        $this->get("/api/v1/users/{$id}");
        $this->assertEquals(404, $this->statusCode());
    }

    public function test_it_returns_404_when_user_not_found(): void
    {
        $this->delete('/api/v1/users/00000000-0000-4000-8000-000000000000');

        $this->assertEquals(404, $this->statusCode());
    }
}
