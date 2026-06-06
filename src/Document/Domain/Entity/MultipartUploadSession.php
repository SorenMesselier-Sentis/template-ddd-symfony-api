<?php

declare(strict_types=1);

namespace App\Document\Domain\Entity;

use App\Document\Domain\Enum\MultipartUploadStatus;
use App\Document\Domain\Exception\UploadSessionNotFoundException;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Document\Domain\ValueObject\PartNumber;

final class MultipartUploadSession
{
    /** @var array<int, MultipartUploadPart> */
    private array $parts = [];

    private function __construct(
        private readonly string $uploadId,
        private readonly DocumentId $documentId,
        private readonly OwnerId $ownerId,
        private readonly BucketName $bucketName,
        private readonly ObjectPath $objectPath,
        private readonly string $originalName,
        private readonly MimeType $mimeType,
        private readonly int $totalSize,
        private MultipartUploadStatus $status,
        private array $partsData,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->hydratePartsFromData($partsData);
    }

    public static function initiate(
        string $uploadId,
        DocumentId $documentId,
        OwnerId $ownerId,
        BucketName $bucketName,
        ObjectPath $objectPath,
        string $originalName,
        MimeType $mimeType,
        int $totalSize,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            uploadId: $uploadId,
            documentId: $documentId,
            ownerId: $ownerId,
            bucketName: $bucketName,
            objectPath: $objectPath,
            originalName: $originalName,
            mimeType: $mimeType,
            totalSize: $totalSize,
            status: MultipartUploadStatus::ACTIVE,
            partsData: [],
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function registerPart(PartNumber $partNumber, string $etag, int $size): void
    {
        $this->ensureActive();
        $this->parts[$partNumber->value()] = new MultipartUploadPart($partNumber, $etag, $size);
        $this->partsData = $this->serializeParts();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function complete(): void
    {
        $this->ensureActive();
        $this->status = MultipartUploadStatus::COMPLETED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function abort(): void
    {
        $this->ensureActive();
        $this->status = MultipartUploadStatus::ABORTED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return list<array{partNumber: int, etag: string}>
     */
    public function partsForCompletion(): array
    {
        $parts = $this->parts;
        ksort($parts);

        return array_map(
            static fn (MultipartUploadPart $part): array => [
                'partNumber' => $part->partNumber()->value(),
                'etag' => $part->etag(),
            ],
            array_values($parts),
        );
    }

    public function belongsTo(OwnerId $ownerId): bool
    {
        return $this->ownerId->equals($ownerId);
    }

    public function uploadId(): string
    {
        return $this->uploadId;
    }

    public function documentId(): DocumentId
    {
        return $this->documentId;
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

    public function mimeType(): MimeType
    {
        return $this->mimeType;
    }

    public function totalSize(): int
    {
        return $this->totalSize;
    }

    public function status(): MultipartUploadStatus
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

    /**
     * @return list<array{partNumber: int, etag: string, size: int}>
     */
    public function partsData(): array
    {
        return $this->partsData;
    }

    /**
     * @param list<array{partNumber: int, etag: string, size: int}> $partsData
     */
    private function hydratePartsFromData(array $partsData): void
    {
        $this->parts = [];

        foreach ($partsData as $partData) {
            $partNumber = PartNumber::fromInt($partData['partNumber']);
            $this->parts[$partNumber->value()] = new MultipartUploadPart(
                $partNumber,
                $partData['etag'],
                $partData['size'],
            );
        }
    }

    /**
     * @return list<array{partNumber: int, etag: string, size: int}>
     */
    private function serializeParts(): array
    {
        $parts = $this->parts;
        ksort($parts);

        return array_map(
            static fn (MultipartUploadPart $part): array => [
                'partNumber' => $part->partNumber()->value(),
                'etag' => $part->etag(),
                'size' => $part->size(),
            ],
            array_values($parts),
        );
    }

    /**
     * @return list<MultipartUploadPart>
     */
    public function parts(): array
    {
        $parts = $this->parts;
        ksort($parts);

        return array_values($parts);
    }

    private function ensureActive(): void
    {
        if (MultipartUploadStatus::ACTIVE !== $this->status) {
            throw UploadSessionNotFoundException::withUploadId($this->uploadId);
        }
    }
}
