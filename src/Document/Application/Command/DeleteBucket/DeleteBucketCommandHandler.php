<?php

declare(strict_types=1);

namespace App\Document\Application\Command\DeleteBucket;

use App\Document\Domain\Exception\BucketNotEmptyException;
use App\Document\Domain\Exception\BucketNotFoundException;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\Storage\BucketManagerInterface;
use App\Document\Domain\ValueObject\BucketName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteBucketCommandHandler
{
    public function __construct(
        private readonly BucketManagerInterface $bucketManager,
        private readonly BucketExistenceCheckerInterface $bucketChecker,
        private readonly DocumentRepositoryInterface $documentRepository,
    ) {
    }

    public function __invoke(DeleteBucketCommand $command): DeleteBucketResult
    {
        $bucket = BucketName::fromString($command->name);

        if (!$this->bucketChecker->exists($bucket)) {
            throw BucketNotFoundException::withName($bucket->value());
        }

        if ($this->documentRepository->hasActiveDocumentsInBucket($bucket)) {
            throw BucketNotEmptyException::withName($bucket->value());
        }

        $this->bucketManager->delete($bucket);

        return new DeleteBucketResult(name: $bucket->value());
    }
}
