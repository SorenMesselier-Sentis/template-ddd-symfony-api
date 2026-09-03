<?php

declare(strict_types=1);

namespace App\Tests\Http\Document;

use App\Tests\Http\HttpTestCase;
use App\Tests\Support\ObjectStorageTestTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadDocumentControllerTest extends HttpTestCase
{
    use ObjectStorageTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isObjectStorageAvailable()) {
            $this->ensureBucket('documents');
        }
    }

    public function testUploadDocumentRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/documents');

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testUploadDocumentReturns404ForMissingBucket(): void
    {
        if (!$this->isObjectStorageAvailable()) {
            $this->markTestSkipped('Object storage is not available.');
        }

        $client = $this->createAuthenticatedClient('user');
        $file = $this->createTempPdf('missing-bucket.pdf');

        $client->request(
            'POST',
            '/api/v1/documents',
            parameters: ['bucket' => 'missing-bucket'],
            files: ['file' => $file],
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertSame('document.bucket_not_found', $payload['error']['code']);
    }

    public function testUploadDocumentRejectsInvalidMimeType(): void
    {
        if (!$this->isObjectStorageAvailable()) {
            $this->markTestSkipped('Object storage is not available.');
        }

        $client = $this->createAuthenticatedClient('user');
        $path = tempnam(sys_get_temp_dir(), 'doc');
        file_put_contents($path, 'plain text');
        $file = new UploadedFile($path, 'notes.txt', 'text/plain', null, true);

        $client->request(
            'POST',
            '/api/v1/documents',
            parameters: ['bucket' => 'documents'],
            files: ['file' => $file],
        );

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(422, $client->getResponse()->getStatusCode());
        $this->assertSame('document.invalid_mime_type', $payload['error']['code']);
    }

    public function testUploadDocumentSucceeds(): void
    {
        if (!$this->isObjectStorageAvailable()) {
            $this->markTestSkipped('Object storage is not available.');
        }

        $client = $this->createAuthenticatedClient('user');
        $file = $this->createTempPdf('invoice.pdf');

        $client->request(
            'POST',
            '/api/v1/documents',
            parameters: ['bucket' => 'documents'],
            files: ['file' => $file],
        );

        $response = $client->getResponse();
        $this->assertJsonEnvelope($response, 201);

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        // DocumentResponseData builds a plain array with hardcoded camelCase keys (not a
        // Shared\Domain\Bus\Query\Response DTO), so it does not go through ResponseNormalizer /
        // the app-wide snake_case name converter — pre-existing inconsistency, out of scope here.
        $this->assertSame('invoice.pdf', $payload['data']['originalName']);
        $this->assertSame('application/pdf', $payload['data']['mimeType']);
        $this->assertSame('documents', $payload['data']['bucket']);
        $this->assertSame('active', $payload['data']['status']);
        $this->assertArrayNotHasKey('objectPath', $payload['data']);
        $this->assertArrayHasKey('ownerId', $payload['data']);
    }

    private function createTempPdf(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, '%PDF-1.4 test');

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }
}
