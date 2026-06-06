<?php

declare(strict_types=1);

namespace App\Document\Domain\Repository;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\ValueObject\DocumentId;

interface DocumentRepositoryInterface
{
    public function save(Document $document): void;

    public function findById(DocumentId $id): ?Document;

    public function findByIdIncludingDeleted(DocumentId $id): ?Document;
}
