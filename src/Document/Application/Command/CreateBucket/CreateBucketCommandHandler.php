<?php

declare(strict_types=1);

namespace App\Document\Application\Command\CreateBucket;

use App\Document\Domain\Exception\BucketAlreadyExistsException;
use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\Storage\BucketManagerInterface;
use App\Document\Domain\ValueObject\BucketName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateBucketCommandHandler
{
    public function __construct(
        private readonly BucketManagerInterface $bucketManager,
        private readonly BucketExistenceCheckerInterface $bucketChecker,
    ) {
    }

    public function __invoke(CreateBucketCommand $command): CreateBucketResult
    {
        $bucket = BucketName::fromString($command->name);

        if ($this->bucketChecker->exists($bucket)) {
            throw BucketAlreadyExistsException::withName($bucket->value());
        }

        $this->bucketManager->create($bucket);

        foreach ($this->bucketManager->list() as $bucketInfo) {
            if ($bucketInfo->name->equals($bucket)) {
                return new CreateBucketResult(
                    name: $bucket->value(),
                    createdAt: $bucketInfo->createdAt->format(\DateTimeInterface::ATOM),
                );
            }
        }

        return new CreateBucketResult(
            name: $bucket->value(),
            createdAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        );
    }
}
