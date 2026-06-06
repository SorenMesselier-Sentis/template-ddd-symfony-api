<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Persistence\Doctrine\Repository;

use App\Document\Domain\Entity\MultipartUploadSession;
use App\Document\Domain\Enum\MultipartUploadStatus;
use App\Document\Domain\Repository\MultipartUploadSessionRepositoryInterface;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineMultipartUploadSessionRepository implements MultipartUploadSessionRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function save(MultipartUploadSession $session): void
    {
        $this->saveEntity($this->em, $session);
    }

    public function findActiveByUploadId(string $uploadId): ?MultipartUploadSession
    {
        /** @var MultipartUploadSession|null $session */
        $session = $this->em->find(MultipartUploadSession::class, $uploadId);

        if (null !== $session && MultipartUploadStatus::ACTIVE !== $session->status()) {
            return null;
        }

        return $session;
    }

    public function findActiveByUploadIdAndOwner(string $uploadId, OwnerId $ownerId): ?MultipartUploadSession
    {
        $session = $this->findActiveByUploadId($uploadId);

        if (null === $session || !$session->belongsTo($ownerId)) {
            return null;
        }

        return $session;
    }
}
