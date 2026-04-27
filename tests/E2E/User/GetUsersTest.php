<?php

declare(strict_types=1);

namespace App\Tests\E2E\User;

use App\Tests\E2E\ApiTestCase;

final class GetUsersTest extends ApiTestCase
{
    public function test_it_lists_users_with_pagination(): void
    {
        $response = $this->get('/api/v1/users', ['page' => 1, 'limit' => 20]);

        $this->assertEquals(200, $this->statusCode());
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('total', $response['meta']);
        $this->assertArrayHasKey('page', $response['meta']);
        $this->assertArrayHasKey('per_page', $response['meta']);
        $this->assertArrayHasKey('pages', $response['meta']);
    }

    public function test_it_filters_by_email(): void
    {
        $this->post('/api/v1/users', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'filter.test@example.com',
            'password' => 'secret1234',
        ]);

        $response = $this->get('/api/v1/users', ['email' => 'filter.test@example.com']);

        $this->assertEquals(200, $this->statusCode());
        $this->assertCount(1, $response['data']);
        $this->assertEquals('filter.test@example.com', $response['data'][0]['email']);
    }

    public function test_it_sorts_descending(): void
    {
        $response = $this->get('/api/v1/users', ['sort' => '-createdAt']);

        $this->assertEquals(200, $this->statusCode());
    }
}
