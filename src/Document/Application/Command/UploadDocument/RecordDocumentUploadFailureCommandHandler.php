<?php

declare(strict_types=1);

namespace App\Document\Application\Command\UploadDocument;

use App\Document\Domain\Enum\UploadResultStatus;
use App\Document\Domain\Event\DocumentUploaded;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'audit.bus')]
final class RecordDocumentUploadFailureCommandHandler
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(RecordDocumentUploadFailureCommand $command): void
    {
        $this->eventBus->publish(new DocumentUploaded(
            aggregateId: $command->documentId,
            ownerId: $command->ownerId,
            bucketName: $command->bucket,
            objectPath: $command->objectPath,
            originalName: $command->originalName,
            size: $command->size,
            mimeType: $command->mimeType,
            status: UploadResultStatus::FAILED,
        ));
    }
}
