<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class PatchUserControllerTest extends HttpTestCase
{
    public function testPatchUser(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', '/api/v1/users');
        $list = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $id = $list['data'][0]['id'];

        $client->request(
            'PATCH',
            '/api/v1/users/'.$id,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['firstName' => 'Updated'], JSON_THROW_ON_ERROR),
        );

        $this->assertSame(204, $client->getResponse()->getStatusCode());
    }
}
