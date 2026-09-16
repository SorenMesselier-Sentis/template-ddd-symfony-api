<?php

declare(strict_types=1);

namespace App\User\Application\Privacy;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Privacy\PersonalDataAnonymizerInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class UserPersonalDataAnonymizer implements PersonalDataAnonymizerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function key(): string
    {
        return 'user';
    }

    public function anonymizeExpired(\DateTimeImmutable $before): int
    {
        $users = $this->repository->findDeletedBefore($before);

        foreach ($users as $user) {
            $user->anonymize();
            $this->repository->save($user);
            $this->eventBus->publish(...$user->pullDomainEvents());
        }

        return \count($users);
    }
}
