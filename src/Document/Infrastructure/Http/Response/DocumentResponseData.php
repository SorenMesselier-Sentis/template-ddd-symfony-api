<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Http\Response;

use App\Document\Application\Command\UploadDocument\UploadDocumentResult;
use App\Document\Domain\Entity\Document;

final class DocumentResponseData
{
    /**
     * @return array<string, mixed>
     */
    public static function fromDocument(Document $document): array
    {
        return [
            'id' => $document->id()->value(),
            'originalName' => $document->originalName(),
            'size' => $document->size(),
            'mimeType' => $document->mimeType()->value(),
            'bucket' => $document->bucketName()->value(),
            'ownerId' => $document->ownerId()->value(),
            'status' => $document->status()->value,
            'createdAt' => $document->createdAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $document->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromUploadResult(UploadDocumentResult $result): array
    {
        return [
            'id' => $result->id,
            'originalName' => $result->originalName,
            'size' => $result->size,
            'mimeType' => $result->mimeType,
            'bucket' => $result->bucket,
            'ownerId' => $result->ownerId,
            'status' => $result->status,
            'createdAt' => $result->createdAt,
            'updatedAt' => $result->updatedAt,
        ];
    }
}
