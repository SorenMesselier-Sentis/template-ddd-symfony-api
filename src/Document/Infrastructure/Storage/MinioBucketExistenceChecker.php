<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Storage;

use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\ValueObject\BucketName;
use Aws\S3\S3Client;

final class MinioBucketExistenceChecker implements BucketExistenceCheckerInterface
{
    private readonly S3Client $client;

    public function __construct(
        string $endpoint,
        string $accessKey,
        string $secretKey,
        bool $useSsl,
    ) {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
            'http' => [
                'verify' => $useSsl,
            ],
        ]);
    }

    public function exists(BucketName $bucket): bool
    {
        return $this->client->doesBucketExist($bucket->value());
    }
}
