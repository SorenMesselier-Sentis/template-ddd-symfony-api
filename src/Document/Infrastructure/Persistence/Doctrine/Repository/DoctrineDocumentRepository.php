<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence\Doctrine\Repository;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\Enum\DocumentStatus;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Filter\Cursor;
use App\Shared\Domain\Filter\CursorPage;
use App\Shared\Domain\Filter\CursorPagination;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineFilterApplier;
use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class DoctrineDocumentRepository implements DocumentRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function save(Document $document): void
    {
        $this->saveEntity($this->em, $document);
    }

    public function delete(Document $document): void
    {
        $this->deleteEntity($this->em, $document);
    }

    public function findById(DocumentId $id): ?Document
    {
        /** @var Document|null $document */
        $document = $this->em->find(Document::class, $id);

        if (null !== $document && DocumentStatus::DELETED === $document->status()) {
            return null;
        }

        return $document;
    }

    public function findByIdIncludingDeleted(DocumentId $id): ?Document
    {
        $document = $this->em->find(Document::class, $id);

        return $document instanceof Document ? $document : null;
    }

    public function hasActiveDocumentsInBucket(BucketName $bucket): bool
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(Document::class, 'd')
            ->where('d.bucketName = :bucket')
            ->andWhere('d.status = :status')
            ->setParameter('bucket', $bucket->value())
            ->setParameter('status', DocumentStatus::ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function findByOwnerId(OwnerId $ownerId): array
    {
        /* @var list<Document> */
        return $this->activeByOwnerQueryBuilder($ownerId)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByOwnerIdIncludingDeleted(OwnerId $ownerId): array
    {
        /* @var list<Document> */
        return $this->em->createQueryBuilder()
            ->select('d')
            ->from(Document::class, 'd')
            ->where('d.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByOwnerIdAndFilters(OwnerId $ownerId, Filters $filters): array
    {
        $qb = $this->activeByOwnerQueryBuilder($ownerId);
        DoctrineFilterApplier::apply($qb, $filters, 'd');

        /* @var list<Document> */
        return $qb->getQuery()->getResult();
    }

    public function countByOwnerIdAndFilters(OwnerId $ownerId, Filters $filters): int
    {
        $qb = $this->activeByOwnerQueryBuilder($ownerId)
            ->select('COUNT(d.id)');

        DoctrineFilterApplier::applyFilters($qb, $filters, 'd');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return CursorPage<Document> */
    public function findByOwnerIdAndFiltersCursor(OwnerId $ownerId, Filters $filters, CursorPagination $cursorPagination): CursorPage
    {
        $qb = $this->activeByOwnerQueryBuilder($ownerId);
        DoctrineFilterApplier::applyFilters($qb, $filters, 'd');

        if (null !== $cursorPagination->after) {
            $qb->andWhere('(d.createdAt < :cursorCreatedAt) OR (d.createdAt = :cursorCreatedAt AND d.id < :cursorId)')
                ->setParameter('cursorCreatedAt', $cursorPagination->after->createdAt)
                ->setParameter('cursorId', DocumentId::fromString($cursorPagination->after->id));
        }

        $qb->orderBy('d.createdAt', 'DESC')
            ->addOrderBy('d.id', 'DESC')
            ->setMaxResults($cursorPagination->limit + 1);

        /** @var list<Document> $rows */
        $rows = $qb->getQuery()->getResult();

        $hasMore = \count($rows) > $cursorPagination->limit;
        $items = \array_slice($rows, 0, $cursorPagination->limit);

        $nextCursor = null;
        $last = end($items);

        if ($hasMore && false !== $last) {
            $nextCursor = (new Cursor($last->createdAt(), $last->id()->value()))->encode();
        }

        return new CursorPage($items, $nextCursor);
    }

    private function activeByOwnerQueryBuilder(OwnerId $ownerId): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('d')
            ->from(Document::class, 'd')
            ->where('d.ownerId = :ownerId')
            ->andWhere('d.status = :status')
            ->setParameter('ownerId', $ownerId)
            ->setParameter('status', DocumentStatus::ACTIVE);
    }
}
