<?php

declare(strict_types=1);

namespace App\Tests\Http\Document;

use App\Tests\Http\HttpTestCase;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadDocumentControllerTest extends HttpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMinioAvailable()) {
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
        if (!$this->isMinioAvailable()) {
            $this->markTestSkipped('MinIO is not available.');
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
        if (!$this->isMinioAvailable()) {
            $this->markTestSkipped('MinIO is not available.');
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
        if (!$this->isMinioAvailable()) {
            $this->markTestSkipped('MinIO is not available.');
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

    private function isMinioAvailable(): bool
    {
        $endpoint = $_ENV['MINIO_ENDPOINT'] ?? 'http://minio:9000';

        try {
            $client = new S3Client([
                'version' => 'latest',
                'region' => 'us-east-1',
                'endpoint' => $endpoint,
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => $_ENV['MINIO_ACCESS_KEY'] ?? 'minio',
                    'secret' => $_ENV['MINIO_SECRET_KEY'] ?? 'change_me',
                ],
                'http' => ['connect_timeout' => 1, 'timeout' => 1],
            ]);
            $client->listBuckets();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function ensureBucket(string $name): void
    {
        $client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $_ENV['MINIO_ENDPOINT'] ?? 'http://minio:9000',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $_ENV['MINIO_ACCESS_KEY'] ?? 'minio',
                'secret' => $_ENV['MINIO_SECRET_KEY'] ?? 'change_me',
            ],
        ]);

        if (!$client->doesBucketExist($name)) {
            $client->createBucket(['Bucket' => $name]);
        }
    }
}
