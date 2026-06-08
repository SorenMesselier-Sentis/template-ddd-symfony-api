<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence\Doctrine\Repository;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\Enum\DocumentStatus;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\OwnerId;
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
        /** @var Document|null $document */
        return $this->em->find(Document::class, $id);
    }

    public function hasActiveDocumentsInBucket(BucketName $bucket): bool
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(Document::class, 'd')
            ->where('d.bucketName = :bucket')
            ->andWhere('d.status = :status')
            ->setParameter('bucket', $bucket)
            ->setParameter('status', DocumentStatus::ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function findByOwnerId(OwnerId $ownerId): array
    {
        /** @var list<Document> */
        return $this->activeByOwnerQueryBuilder($ownerId)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByOwnerIdAndFilters(OwnerId $ownerId, Filters $filters): array
    {
        $qb = $this->activeByOwnerQueryBuilder($ownerId);
        DoctrineFilterApplier::apply($qb, $filters, 'd');

        /** @var list<Document> */
        return $qb->getQuery()->getResult();
    }

    public function countByOwnerIdAndFilters(OwnerId $ownerId, Filters $filters): int
    {
        $qb = $this->activeByOwnerQueryBuilder($ownerId)
            ->select('COUNT(d.id)');

        DoctrineFilterApplier::applyFilters($qb, $filters, 'd');

        return (int) $qb->getQuery()->getSingleScalarResult();
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
