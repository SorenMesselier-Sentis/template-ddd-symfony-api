<?php

declare(strict_types=1);

namespace App\Document\Application\Query\GetDocuments;

use App\Document\Domain\Entity\Document;
use App\Shared\Domain\Bus\Query\Response;

final class DocumentItemResponse implements Response
{
    public readonly string $id;
    public readonly string $originalName;
    public readonly int $size;
    public readonly string $mimeType;
    public readonly string $bucket;
    public readonly string $ownerId;
    public readonly string $status;
    public readonly string $createdAt;
    public readonly string $updatedAt;

    public function __construct(Document $document)
    {
        $this->id = $document->id()->value();
        $this->originalName = $document->originalName();
        $this->size = $document->size();
        $this->mimeType = $document->mimeType()->value();
        $this->bucket = $document->bucketName()->value();
        $this->ownerId = $document->ownerId()->value();
        $this->status = $document->status()->value;
        $this->createdAt = $document->createdAt()->format(\DateTimeInterface::ATOM);
        $this->updatedAt = $document->updatedAt()->format(\DateTimeInterface::ATOM);
    }
}
