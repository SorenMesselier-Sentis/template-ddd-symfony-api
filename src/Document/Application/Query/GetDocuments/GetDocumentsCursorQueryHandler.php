<?php

declare(strict_types=1);

namespace App\Document\Application\Query\GetDocuments;

use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetDocumentsCursorQueryHandler
{
    public function __construct(
        private readonly DocumentRepositoryInterface $repository,
        private readonly OwnerContextInterface $ownerContext,
    ) {
    }

    public function __invoke(GetDocumentsCursorQuery $query): DocumentsCursorResponse
    {
        $ownerId = $this->ownerContext->ownerId();
        $page = $this->repository->findByOwnerIdAndFiltersCursor($ownerId, $query->filters, $query->cursorPagination);

        return new DocumentsCursorResponse(
            documents: array_map(
                static fn ($document): DocumentItemResponse => new DocumentItemResponse($document),
                $page->items,
            ),
            limit: $query->cursorPagination->limit,
            hasMore: null !== $page->nextCursor,
            nextCursor: $page->nextCursor,
        );
    }
}
