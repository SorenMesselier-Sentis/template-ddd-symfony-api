<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class UpdateUserRolesControllerTest extends HttpTestCase
{
    public function testUpdateRolesAsAdmin(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', '/api/v1/users');
        $list = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $id = $list['data'][0]['id'];

        $client->request(
            'PUT',
            '/api/v1/users/'.$id.'/roles',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['roles' => ['ROLE_USER']], JSON_THROW_ON_ERROR),
        );

        $this->assertSame(204, $client->getResponse()->getStatusCode());
    }
}
