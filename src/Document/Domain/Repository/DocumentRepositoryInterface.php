<?php

declare(strict_types=1);

namespace App\Document\Domain\Repository;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Filter\CursorPage;
use App\Shared\Domain\Filter\CursorPagination;
use App\Shared\Domain\Filter\Filters;

interface DocumentRepositoryInterface
{
    public function save(Document $document): void;

    public function findById(DocumentId $id): ?Document;

    public function findByIdIncludingDeleted(DocumentId $id): ?Document;

    public function hasActiveDocumentsInBucket(BucketName $bucket): bool;

    /**
     * @return list<Document>
     */
    public function findByOwnerId(OwnerId $ownerId): array;

    /**
     * @return list<Document>
     */
    public function findByOwnerIdAndFilters(OwnerId $ownerId, Filters $filters): array;

    public function countByOwnerIdAndFilters(OwnerId $ownerId, Filters $filters): int;

    /** @return CursorPage<Document> */
    public function findByOwnerIdAndFiltersCursor(OwnerId $ownerId, Filters $filters, CursorPagination $cursorPagination): CursorPage;
}
