<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;

final class GetUserControllerTest extends HttpTestCase
{
    public function testGetUserById(): void
    {
        $client = $this->createAuthenticatedClient('admin');
        $client->request('GET', '/api/v1/users');
        $list = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $id = $list['data'][0]['id'];

        $client->request('GET', '/api/v1/users/'.$id);
        $this->assertJsonEnvelope($client->getResponse(), 200);
    }
}
