<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Scheduler;

use App\Shared\Infrastructure\Scheduler\Message\CleanupExpiredRefreshTokens;
use App\Shared\Infrastructure\Scheduler\Message\CleanupExpiredUserTokens;
use App\Shared\Infrastructure\Scheduler\Message\CleanupStaleOutboxMessages;
use App\Shared\Infrastructure\Scheduler\Message\RelayOutboxMessages;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('default')]
final class DefaultSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function getSchedule(): Schedule
    {
        $utc = new \DateTimeZone('UTC');

        return (new Schedule())
            ->stateful($this->cache)
            ->lock($this->lockFactory->createLock('scheduler_default'))
            ->processOnlyLastMissedRun(true)
            ->add(
                RecurringMessage::every('10 seconds', new RelayOutboxMessages()),
                RecurringMessage::cron('0 2 * * *', new CleanupExpiredRefreshTokens(), $utc),
                RecurringMessage::cron('0 2 * * *', new CleanupExpiredUserTokens(), $utc),
                RecurringMessage::cron('0 3 * * *', new CleanupStaleOutboxMessages(), $utc),
            );
    }
}
