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

    public function seed(): void
    {
        if (!$this->isObjectStorageAvailable()) {
            return;
        }

        foreach (DocumentFixtureCatalog::bucketNames() as $bucketName) {
            $bucket = BucketName::fromString($bucketName);

            if (!$this->bucketChecker->exists($bucket)) {
                $this->bucketManager->create($bucket);
            }
        }

        foreach (DocumentFixtureCatalog::definitions() as $definition) {
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
