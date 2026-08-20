<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\RealTime;

use App\Shared\Infrastructure\RealTime\MercureRealtimePublisher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class MercureRealtimePublisherTest extends TestCase
{
    #[Test]
    public function itPublishesAnUpdateToTheGivenTopic(): void
    {
        $hub = $this->createMock(HubInterface::class);
        $hub->expects(self::once())
            ->method('publish')
            ->with(self::callback(function (Update $update): bool {
                self::assertSame(['/users/123/notifications'], $update->getTopics());
                self::assertSame(['subject' => 'Hi'], json_decode($update->getData(), true, 512, \JSON_THROW_ON_ERROR));
                self::assertTrue($update->isPrivate());

                return true;
            }));

        $publisher = new MercureRealtimePublisher($hub);
        $publisher->publish('/users/123/notifications', ['subject' => 'Hi']);
    }

    #[Test]
    public function itCanPublishAPublicUpdate(): void
    {
        $hub = $this->createMock(HubInterface::class);
        $hub->expects(self::once())
            ->method('publish')
            ->with(self::callback(fn (Update $update): bool => !$update->isPrivate()));

        $publisher = new MercureRealtimePublisher($hub);
        $publisher->publish('/announcements', [], private: false);
    }
}
