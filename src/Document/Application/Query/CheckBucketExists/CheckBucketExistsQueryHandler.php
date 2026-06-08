<?php

declare(strict_types=1);

namespace App\Document\Application\Query\CheckBucketExists;

use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\ValueObject\BucketName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class CheckBucketExistsQueryHandler
{
    public function __construct(
        private readonly BucketExistenceCheckerInterface $bucketChecker,
    ) {
    }

    public function __invoke(CheckBucketExistsQuery $query): CheckBucketExistsResult
    {
        $bucket = BucketName::fromString($query->name);

        return new CheckBucketExistsResult(
            name: $bucket->value(),
            exists: $this->bucketChecker->exists($bucket),
        );
    }
}
