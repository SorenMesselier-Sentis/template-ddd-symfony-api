<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Aws\S3\S3Client;

trait ObjectStorageTestTrait
{
    private function isObjectStorageAvailable(): bool
    {
        try {
            $client = $this->createObjectStorageS3Client();
            $client->listBuckets();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function ensureBucket(string $name): void
    {
        $client = $this->createObjectStorageS3Client();

        if (!$client->doesBucketExist($name)) {
            $client->createBucket(['Bucket' => $name]);
        }
    }

    private function createObjectStorageS3Client(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $this->s3Endpoint(),
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $this->s3AccessKey(),
                'secret' => $this->s3SecretKey(),
            ],
            'http' => ['connect_timeout' => 2, 'timeout' => 2],
        ]);
    }

    private function s3Endpoint(): string
    {
        return $_ENV['S3_ENDPOINT'] ?? 'http://rustfs:9000';
    }

    private function s3AccessKey(): string
    {
        return $_ENV['S3_ACCESS_KEY'] ?? 'rustfsadmin';
    }

    private function s3SecretKey(): string
    {
        return $_ENV['S3_SECRET_KEY'] ?? 'change_me';
    }
}
