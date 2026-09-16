<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Privacy;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Privacy\UserPersonalDataAnonymizer;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class UserPersonalDataAnonymizerTest extends UnitTestCase
{
    public function testKeyIsUser(): void
    {
        $anonymizer = new UserPersonalDataAnonymizer(
            $this->createStub(UserRepositoryInterface::class),
            $this->createStub(EventBusInterface::class),
        );

        $this->assertSame('user', $anonymizer->key());
    }

    public function testAnonymizeExpiredAnonymizesEveryDeletedUserFound(): void
    {
        $userA = UserMother::create();
        $userA->delete();
        $userA->pullDomainEvents();
        $userB = UserMother::create();
        $userB->delete();
        $userB->pullDomainEvents();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())->method('findDeletedBefore')->willReturn([$userA, $userB]);
        $repository->expects($this->exactly(2))->method('save');

        /** @var EventBusInterface&MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects($this->exactly(2))->method('publish');

        $anonymizer = new UserPersonalDataAnonymizer($repository, $eventBus);

        $count = $anonymizer->anonymizeExpired(new \DateTimeImmutable());

        $this->assertSame(2, $count);
        $this->assertSame('deleted_user', $userA->firstName()->value());
        $this->assertSame('deleted_user', $userB->firstName()->value());
    }
}
