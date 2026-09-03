<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messaging;

use App\Document\Domain\Enum\UploadResultStatus;
use App\Document\Domain\Event\DocumentUploaded;
use App\Shared\Infrastructure\Messaging\DomainEventPayloadExtractor;
use App\Tests\Unit\UnitTestCase;
use App\Webhook\Domain\Event\WebhookSubscriptionCreated;

final class DomainEventPayloadExtractorTest extends UnitTestCase
{
    public function testItExtractsAllPublicPropertiesOfTheEvent(): void
    {
        $event = new DocumentUploaded(
            aggregateId: 'document-id',
            ownerId: 'owner-id',
            bucketName: 'bucket',
            objectPath: 'path/to/file.pdf',
            originalName: 'file.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            status: UploadResultStatus::SUCCESS,
        );

        $payload = DomainEventPayloadExtractor::extract($event);

        $this->assertSame([
            'ownerId' => 'owner-id',
            'bucketName' => 'bucket',
            'objectPath' => 'path/to/file.pdf',
            'originalName' => 'file.pdf',
            'size' => 1024,
            'mimeType' => 'application/pdf',
            'status' => 'success',
        ], $payload);
    }

    public function testItUnwrapsBackedEnumsToTheirScalarValue(): void
    {
        $event = new DocumentUploaded(
            aggregateId: 'document-id',
            ownerId: 'owner-id',
            bucketName: 'bucket',
            objectPath: 'path/to/file.pdf',
            originalName: 'file.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            status: UploadResultStatus::FAILED,
        );

        $payload = DomainEventPayloadExtractor::extract($event);

        $this->assertSame('failed', $payload['status']);
    }

    public function testItReturnsAnEmptyArrayWhenTheEventHasNoPublicProperties(): void
    {
        $event = new WebhookSubscriptionCreated(aggregateId: 'subscription-id');

        $payload = DomainEventPayloadExtractor::extract($event);

        $this->assertSame([], $payload);
    }

    public function testItNeverExposesTheBaseDomainEventPrivateProperties(): void
    {
        $event = new DocumentUploaded(
            aggregateId: 'document-id',
            ownerId: 'owner-id',
            bucketName: 'bucket',
            objectPath: 'path/to/file.pdf',
            originalName: 'file.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            status: UploadResultStatus::SUCCESS,
        );

        $payload = DomainEventPayloadExtractor::extract($event);

        $this->assertArrayNotHasKey('aggregateId', $payload);
        $this->assertArrayNotHasKey('eventId', $payload);
        $this->assertArrayNotHasKey('occurredOn', $payload);
    }
}
