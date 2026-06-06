<?php

declare(strict_types=1);

namespace App\Document\Domain\Entity;

use App\Document\Domain\Enum\DocumentStatus;
use App\Document\Domain\Enum\UploadResultStatus;
use App\Document\Domain\Event\DocumentDeleted;
use App\Document\Domain\Event\DocumentUploaded;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Bus\Event\DomainEvent;

final class Document
{
    /** @var array<int, DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        private readonly DocumentId $id,
        private readonly OwnerId $ownerId,
        private readonly BucketName $bucketName,
        private readonly ObjectPath $objectPath,
        private readonly string $originalName,
        private readonly int $size,
        private readonly MimeType $mimeType,
        private DocumentStatus $status,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        DocumentId $id,
        OwnerId $ownerId,
        BucketName $bucketName,
        ObjectPath $objectPath,
        string $originalName,
        int $size,
        MimeType $mimeType,
    ): self {
        $now = new \DateTimeImmutable();
        $document = new self(
            id: $id,
            ownerId: $ownerId,
            bucketName: $bucketName,
            objectPath: $objectPath,
            originalName: $originalName,
            size: $size,
            mimeType: $mimeType,
            status: DocumentStatus::ACTIVE,
            createdAt: $now,
            updatedAt: $now,
        );

        $document->record(new DocumentUploaded(
            aggregateId: $id->value(),
            ownerId: $ownerId->value(),
            bucketName: $bucketName->value(),
            objectPath: $objectPath->value(),
            originalName: $originalName,
            size: $size,
            mimeType: $mimeType->value(),
            status: UploadResultStatus::SUCCESS,
        ));

        return $document;
    }

    /**
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function id(): DocumentId
    {
        return $this->id;
    }

    public function ownerId(): OwnerId
    {
        return $this->ownerId;
    }

    public function bucketName(): BucketName
    {
        return $this->bucketName;
    }

    public function objectPath(): ObjectPath
    {
        return $this->objectPath;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function mimeType(): MimeType
    {
        return $this->mimeType;
    }

    public function status(): DocumentStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function delete(bool $purge): void
    {
        $this->status = DocumentStatus::DELETED;
        $this->updatedAt = new \DateTimeImmutable();

        $this->record(new DocumentDeleted(
            aggregateId: $this->id->value(),
            ownerId: $this->ownerId->value(),
            purge: $purge,
        ));
    }
}
