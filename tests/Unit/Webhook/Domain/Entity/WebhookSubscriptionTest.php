<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\Webhook\Domain\Mother\WebhookSubscriptionMother;
use App\Webhook\Domain\Event\WebhookSubscriptionCreated;
use App\Webhook\Domain\Event\WebhookSubscriptionDeleted;
use App\Webhook\Domain\Event\WebhookSubscriptionDisabled;
use App\Webhook\Domain\Event\WebhookSubscriptionEnabled;
use App\Webhook\Domain\Event\WebhookSubscriptionSecretRotated;
use App\Webhook\Domain\Event\WebhookSubscriptionUpdated;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionStatus;
use App\Webhook\Domain\ValueObject\WebhookUrl;

final class WebhookSubscriptionTest extends UnitTestCase
{
    public function testCreateDefaultsToActiveAndRecordsEvent(): void
    {
        $entity = WebhookSubscriptionMother::create();

        $this->assertSame(WebhookSubscriptionStatus::ACTIVE, $entity->status());
        $this->assertTrue($entity->isActive());

        $events = $entity->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(WebhookSubscriptionCreated::class, $events[0]);
    }

    public function testSubscribesToMatchesOnlyListedEventNamesWhenActive(): void
    {
        $entity = WebhookSubscriptionMother::create(eventNames: ['document.uploaded', 'user.created']);

        $this->assertTrue($entity->subscribesTo('document.uploaded'));
        $this->assertTrue($entity->subscribesTo('user.created'));
        $this->assertFalse($entity->subscribesTo('user.deleted'));
    }

    public function testSubscribesToIsFalseWhenDisabled(): void
    {
        $entity = WebhookSubscriptionMother::create(eventNames: ['document.uploaded']);
        $entity->disable();

        $this->assertFalse($entity->subscribesTo('document.uploaded'));
    }

    public function testUpdateChangesNameUrlAndEventNames(): void
    {
        $entity = WebhookSubscriptionMother::create();
        $entity->pullDomainEvents();

        $entity->update('New name', WebhookUrl::fromString('https://new.example.com/inbound'), ['task.created']);

        $this->assertSame('New name', $entity->name());
        $this->assertSame('https://new.example.com/inbound', $entity->url()->value());
        $this->assertSame(['task.created'], $entity->eventNames());
        $this->assertInstanceOf(WebhookSubscriptionUpdated::class, $entity->pullDomainEvents()[0]);
    }

    public function testRotateSecretReplacesTheSecretAndRecordsEvent(): void
    {
        $entity = WebhookSubscriptionMother::create(secret: 'old-secret');
        $entity->pullDomainEvents();

        $entity->rotateSecret('new-secret');

        $this->assertSame('new-secret', $entity->secret());
        $this->assertInstanceOf(WebhookSubscriptionSecretRotated::class, $entity->pullDomainEvents()[0]);
    }

    public function testDisableThenEnableRoundTrips(): void
    {
        $entity = WebhookSubscriptionMother::create();
        $entity->pullDomainEvents();

        $entity->disable();
        $this->assertSame(WebhookSubscriptionStatus::DISABLED, $entity->status());
        $this->assertInstanceOf(WebhookSubscriptionDisabled::class, $entity->pullDomainEvents()[0]);

        $entity->enable();
        $this->assertSame(WebhookSubscriptionStatus::ACTIVE, $entity->status());
        $this->assertTrue($entity->isActive());
        $this->assertInstanceOf(WebhookSubscriptionEnabled::class, $entity->pullDomainEvents()[0]);
    }

    public function testDeleteTransitionsStatusAndRecordsEvent(): void
    {
        $entity = WebhookSubscriptionMother::create();
        $entity->pullDomainEvents();

        $entity->delete();

        $this->assertSame(WebhookSubscriptionStatus::DELETED, $entity->status());
        $this->assertFalse($entity->isActive());
        $this->assertInstanceOf(WebhookSubscriptionDeleted::class, $entity->pullDomainEvents()[0]);
    }
}
