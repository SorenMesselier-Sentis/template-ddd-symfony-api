<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence\Doctrine\Repository;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\Enum\DocumentStatus;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;

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
}
