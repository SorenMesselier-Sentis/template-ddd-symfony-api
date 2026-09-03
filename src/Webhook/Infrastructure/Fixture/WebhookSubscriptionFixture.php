<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Fixture;

use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Shared\Infrastructure\Fixture\FixtureReference;
use App\Webhook\Domain\Entity\WebhookSubscription;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use App\Webhook\Domain\ValueObject\WebhookUrl;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class WebhookSubscriptionFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $entity = WebhookSubscription::create(
            id: WebhookSubscriptionId::fromString(FixtureData::WEBHOOK_SUBSCRIPTION_TEST_ID),
            name: FixtureData::WEBHOOK_SUBSCRIPTION_TEST_NAME,
            url: WebhookUrl::fromString(FixtureData::WEBHOOK_SUBSCRIPTION_TEST_URL),
            secret: FixtureData::WEBHOOK_SUBSCRIPTION_TEST_SECRET,
            eventNames: [FixtureData::WEBHOOK_SUBSCRIPTION_TEST_EVENT_NAME],
        );
        $entity->pullDomainEvents();

        $manager->persist($entity);
        $manager->flush();

        $this->addReference(FixtureReference::WEBHOOK_SUBSCRIPTION_TEST, $entity);
    }
}
