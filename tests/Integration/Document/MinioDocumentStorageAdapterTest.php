<?php

declare(strict_types=1);

namespace App\Tests\Integration\Document;

use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Infrastructure\Storage\MinioDocumentStorageAdapter;
use App\Tests\Integration\IntegrationTestCase;
use Aws\S3\S3Client;

final class MinioDocumentStorageAdapterTest extends IntegrationTestCase
{
    private ?MinioDocumentStorageAdapter $adapter = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->isMinioAvailable()) {
            $this->markTestSkipped('MinIO is not available.');
        }

        $this->ensureBucket('integration-documents');
        $this->adapter = new MinioDocumentStorageAdapter(
            endpoint: $_ENV['MINIO_ENDPOINT'] ?? 'http://minio:9000',
            accessKey: $_ENV['MINIO_ACCESS_KEY'] ?? 'minio',
            secretKey: $_ENV['MINIO_SECRET_KEY'] ?? 'change_me',
            useSsl: false,
        );
    }

    public function testItUploadsObjectToMinio(): void
    {
        $bucket = BucketName::fromString('integration-documents');
        $path = ObjectPath::fromString('integration/test.txt');

        $this->adapter->upload($bucket, $path, 'hello', MimeType::fromString('text/plain'));

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

        $object = $client->getObject([
            'Bucket' => $bucket->value(),
            'Key' => $path->value(),
        ]);

        $this->assertSame('hello', (string) $object['Body']);
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
