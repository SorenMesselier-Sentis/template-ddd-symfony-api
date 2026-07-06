<?php

declare(strict_types=1);

namespace App\Tests\Http\Document;

use App\Tests\Http\HttpTestCase;
use App\Tests\Support\ObjectStorageTestTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocumentSecurityHttpTest extends HttpTestCase
{
    use ObjectStorageTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isObjectStorageAvailable()) {
            $this->ensureBucket('documents');
        }
    }

    public function testDocumentEndpointsRequireAuthentication(): void
    {
        $client = static::createClient();

        $client->request('DELETE', '/api/v1/documents/00000000-0000-0000-0000-000000000001');
        $this->assertSame(401, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/v1/documents/00000000-0000-0000-0000-000000000001/presigned-url');
        $this->assertSame(401, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/v1/buckets');
        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testBucketManagementRequiresAdminRole(): void
    {
        $client = $this->createAuthenticatedClient('user');

        $client->request(
            'POST',
            '/api/v1/buckets',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'reports'], JSON_THROW_ON_ERROR),
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertSame('insufficient_privileges', $payload['error']['code']);

        $client->request('GET', '/api/v1/buckets');
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminCanListBuckets(): void
    {
        $client = $this->createAuthenticatedClient('admin');

        $client->request('GET', '/api/v1/buckets');

        $this->assertJsonEnvelope($client->getResponse(), 200);
    }

    public function testUploadResponseDoesNotExposeObjectPath(): void
    {
        if (!$this->isObjectStorageAvailable()) {
            $this->markTestSkipped('RustFS is not available.');
        }

        $client = $this->createAuthenticatedClient('user');
        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, '%PDF-1.4 test');
        $file = new UploadedFile($path, 'secure.pdf', 'application/pdf', null, true);

        $client->request(
            'POST',
            '/api/v1/documents',
            parameters: ['bucket' => 'documents'],
            files: ['file' => $file],
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(201, $client->getResponse()->getStatusCode());
        $this->assertArrayNotHasKey('objectPath', $payload['data']);
    }
}
