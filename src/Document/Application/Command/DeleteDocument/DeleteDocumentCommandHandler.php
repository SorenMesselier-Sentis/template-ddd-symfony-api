<?php

declare(strict_types=1);

namespace App\Document\Application\Command\DeleteDocument;

use App\Document\Domain\Enum\DocumentStatus;
use App\Document\Domain\Exception\DocumentNotFoundException;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\Storage\DocumentStorageInterface;
use App\Document\Domain\ValueObject\DocumentId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Exception\ForbiddenException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteDocumentCommandHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly DocumentRepositoryInterface $repository,
        private readonly DocumentStorageInterface $storage,
        private readonly EventBusInterface $eventBus,
        private readonly OwnerContextInterface $ownerContext,
    ) {
    }

    public function __invoke(DeleteDocumentCommand $command): DeleteDocumentResult
    {
        $documentId = DocumentId::fromString($command->documentId);
        $requesterId = $this->ownerContext->ownerId();
        $isAdmin = $this->isAdmin();

        $document = $this->repository->findByIdIncludingDeleted($documentId);

        if (null !== $document && !$isAdmin && !$document->ownerId()->equals($requesterId)) {
            throw ForbiddenException::create();
        }

        if (null === $document || DocumentStatus::DELETED === $document->status()) {
            throw DocumentNotFoundException::withId($command->documentId);
        }

        $document->delete($command->purge);
        $this->repository->save($document);

        if ($command->purge) {
            $this->storage->delete($document->bucketName(), $document->objectPath());
        }

        $this->eventBus->publish(...$document->pullDomainEvents());

        return new DeleteDocumentResult(
            documentId: $document->id()->value(),
            status: $document->status()->value,
            purged: $command->purge,
            updatedAt: $document->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
