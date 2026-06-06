<?php

declare(strict_types=1);

namespace App\Document\Application\Query\ListBuckets;

use App\Document\Domain\Storage\BucketManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListBucketsQueryHandler
{
    public function __construct(
        private readonly BucketManagerInterface $bucketManager,
    ) {
    }

    public function __invoke(ListBucketsQuery $query): ListBucketsResult
    {
        $buckets = array_map(
            static fn ($bucketInfo): array => [
                'name' => $bucketInfo->name->value(),
                'createdAt' => $bucketInfo->createdAt->format(\DateTimeInterface::ATOM),
            ],
            $this->bucketManager->list(),
        );

        return new ListBucketsResult(buckets: $buckets);
    }
}
