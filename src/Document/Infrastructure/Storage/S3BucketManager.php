<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Storage;

use App\Document\Domain\Storage\BucketManagerInterface;
use App\Document\Domain\ValueObject\BucketInfo;
use App\Document\Domain\ValueObject\BucketName;
use Aws\S3\S3Client;

final class MinioBucketManager implements BucketManagerInterface
{
    private readonly S3Client $client;

    public function __construct(
        string $endpoint,
        string $accessKey,
        string $secretKey,
        bool $useSsl,
    ) {
        $this->client = MinioS3ClientFactory::create($endpoint, $accessKey, $secretKey, $useSsl);
    }

    public function create(BucketName $bucket): void
    {
        $this->client->createBucket([
            'Bucket' => $bucket->value(),
        ]);
    }

    public function delete(BucketName $bucket): void
    {
        $this->client->deleteBucket([
            'Bucket' => $bucket->value(),
        ]);
    }

    public function list(): array
    {
        $result = AwsS3ResultHelper::toArray($this->client->listBuckets());
        $bucketsList = $result['Buckets'] ?? [];
        $buckets = [];

        if (!\is_array($bucketsList)) {
            return $buckets;
        }

        foreach ($bucketsList as $bucket) {
            if (!\is_array($bucket)) {
                continue;
            }

            $name = $bucket['Name'] ?? null;

            if (!\is_string($name)) {
                continue;
            }

            $creationDate = $bucket['CreationDate'] ?? null;
            $createdAt = $creationDate instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($creationDate)
                : new \DateTimeImmutable();

            $buckets[] = new BucketInfo(
                name: BucketName::fromString($name),
                createdAt: $createdAt,
            );
        }

        return $buckets;
    }
}
