<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Fixture;

use App\Document\Domain\Storage\BucketExistenceCheckerInterface;
use App\Document\Domain\Storage\BucketManagerInterface;
use App\Document\Domain\Storage\DocumentStorageInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\MimeType;

final class DocumentObjectStorageFixtureSeeder
{
    public function __construct(
        private readonly BucketManagerInterface $bucketManager,
        private readonly BucketExistenceCheckerInterface $bucketChecker,
        private readonly DocumentStorageInterface $documentStorage,
    ) {
    }

    /**
     * @param list<array{
     *     reference: ?string,
     *     id: string,
     *     ownerId: string,
     *     bucket: string,
     *     originalName: string,
     *     size: int,
     *     mimeType: string
     * }> $definitions Named catalog entries plus any random ones — must match
     *                 exactly what the caller then persists as `Document` rows,
     *                 so the two stay byte-for-byte in sync (same ids, same
     *                 object paths)
     */
    public function seed(array $definitions): void
    {
        if (!$this->isObjectStorageAvailable()) {
            return;
        }

        foreach (DocumentFixtureCatalog::bucketNames($definitions) as $bucketName) {
            $bucket = BucketName::fromString($bucketName);

            if (!$this->bucketChecker->exists($bucket)) {
                $this->bucketManager->create($bucket);
            }
        }

        foreach ($definitions as $definition) {
            $this->documentStorage->upload(
                bucket: BucketName::fromString($definition['bucket']),
                objectPath: DocumentFixtureCatalog::objectPathFor($definition),
                content: DocumentFixtureCatalog::fixtureContent($definition),
                mimeType: MimeType::fromString($definition['mimeType']),
            );
        }
    }

    private function isObjectStorageAvailable(): bool
    {
        try {
            $this->bucketManager->list();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
