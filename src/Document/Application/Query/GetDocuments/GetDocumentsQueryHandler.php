<?php

declare(strict_types=1);

namespace App\Document\Application\Query\GetDocuments;

use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetDocumentsQueryHandler
{
    public function __construct(
        private readonly DocumentRepositoryInterface $repository,
        private readonly OwnerContextInterface $ownerContext,
    ) {
    }

    public function __invoke(GetDocumentsQuery $query): DocumentsResponse
    {
        $ownerId = $this->ownerContext->ownerId();
        $documents = $this->repository->findByOwnerIdAndFilters($ownerId, $query->filters);
        $total = $this->repository->countByOwnerIdAndFilters($ownerId, $query->filters);

        return new DocumentsResponse(
            documents: array_map(
                static fn ($document): DocumentItemResponse => new DocumentItemResponse($document),
                $documents,
            ),
            total: $total,
            page: $query->filters->pagination->page,
            limit: $query->filters->pagination->limit,
        );
    }
}
