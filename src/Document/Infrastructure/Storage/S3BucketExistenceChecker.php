<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Storage;

use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\ValueObject\BucketName;
use Aws\S3\S3Client;

final class S3BucketExistenceChecker implements BucketExistenceCheckerInterface
{
    private readonly S3Client $client;

    public function __construct(
        string $endpoint,
        string $accessKey,
        string $secretKey,
        bool $useSsl,
    ) {
        $this->client = S3ClientFactory::create($endpoint, $accessKey, $secretKey, $useSsl);
    }

    public function exists(BucketName $bucket): bool
    {
        return $this->client->doesBucketExist($bucket->value());
    }
}
