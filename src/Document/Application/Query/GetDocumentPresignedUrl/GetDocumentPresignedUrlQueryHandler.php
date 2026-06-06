<?php

declare(strict_types=1);

namespace App\Document\Application\Query\GetDocumentPresignedUrl;

use App\Document\Application\Service\PresignedUrlTtlValidator;
use App\Document\Domain\Event\DocumentAccessed;
use App\Document\Domain\Exception\DocumentNotFoundException;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\DocumentPresignedUrlGeneratorInterface;
use App\Document\Domain\ValueObject\DocumentId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetDocumentPresignedUrlQueryHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly DocumentRepositoryInterface $repository,
        private readonly DocumentPresignedUrlGeneratorInterface $presignedUrlGenerator,
        private readonly PresignedUrlTtlValidator $ttlValidator,
        private readonly EventBusInterface $eventBus,
        private readonly OwnerContextInterface $ownerContext,
        private readonly int $defaultTtlSeconds,
    ) {
    }

    public function __invoke(GetDocumentPresignedUrlQuery $query): GetDocumentPresignedUrlResult
    {
        $documentId = DocumentId::fromString($query->documentId);
        $requesterId = $this->ownerContext->ownerId();
        $isAdmin = $this->isAdmin();

        $document = $isAdmin
            ? $this->repository->findByIdIncludingDeleted($documentId)
            : $this->repository->findById($documentId);

        if (null === $document) {
            throw DocumentNotFoundException::withId($query->documentId);
        }

        if (!$isAdmin && !$document->ownerId()->equals($requesterId)) {
            throw ForbiddenException::create();
        }

        $ttlSeconds = $query->ttlSeconds ?? $this->defaultTtlSeconds;
        $this->ttlValidator->validate($ttlSeconds);

        $presignedUrl = $this->presignedUrlGenerator->generatePresignedDownloadUrl($document, $ttlSeconds);

        $this->eventBus->publish(new DocumentAccessed(
            aggregateId: $document->id()->value(),
            requesterId: $requesterId->value(),
        ));

        return new GetDocumentPresignedUrlResult(
            documentId: $document->id()->value(),
            presignedUrl: $presignedUrl->url(),
            expiresIn: $presignedUrl->expiresInSeconds(),
            expiresAt: $presignedUrl->expiresAt()->format(\DateTimeInterface::ATOM),
        );
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
