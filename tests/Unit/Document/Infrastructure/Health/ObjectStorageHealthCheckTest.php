<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Infrastructure\Health;

use App\Document\Infrastructure\Health\ObjectStorageHealthCheck;
use App\Tests\Unit\UnitTestCase;
use Aws\S3\S3Client;

final class ObjectStorageHealthCheckTest extends UnitTestCase
{
    public function testNameIsObjectStorage(): void
    {
        $check = new ObjectStorageHealthCheck(
            endpoint: 'http://rustfs:9000',
            accessKey: 'rustfsadmin',
            secretKey: 'change_me',
            useSsl: false,
        );

        $this->assertSame('object_storage', $check->name());
    }

    public function testCheckReturnsOkWhenObjectStorageResponds(): void
    {
        $capturedTimeout = null;
        $client = new class {
            public function listBuckets(): array
            {
                return ['Buckets' => []];
            }
        };

        $check = new ObjectStorageHealthCheck(
            endpoint: 'http://rustfs:9000',
            accessKey: 'rustfsadmin',
            secretKey: 'change_me',
            useSsl: false,
            clientFactory: static function (
                string $endpoint,
                string $accessKey,
                string $secretKey,
                bool $useSsl,
                ?float $timeoutSeconds,
            ) use ($client, &$capturedTimeout) {
                $capturedTimeout = $timeoutSeconds;

                return $client;
            },
        );

        $status = $check->check();

        $this->assertTrue($status->isOk());
        $this->assertSame(3.0, $capturedTimeout);
    }

    public function testCheckReturnsErrorWhenObjectStorageIsUnreachable(): void
    {
        $check = new ObjectStorageHealthCheck(
            endpoint: 'http://rustfs:9000',
            accessKey: 'rustfsadmin',
            secretKey: 'change_me',
            useSsl: false,
            clientFactory: static function (): S3Client {
                throw new \RuntimeException('Connection timed out after 3 seconds');
            },
        );

        $status = $check->check();

        $this->assertTrue($status->isError());
        $this->assertSame('Connection timed out after 3 seconds', $status->detail());
    }
}
