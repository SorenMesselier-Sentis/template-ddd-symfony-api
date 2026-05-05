<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\EmailMother;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\Tests\Unit\User\Domain\Mother\UserNameMother;
use App\User\Domain\Event\UserCreated;
use App\User\Domain\Event\UserUpdated;
use App\User\Domain\ValueObject\UserStatus;

final class UserTest extends UnitTestCase
{
    public function testItCreatesAUserWithCorrectAttributes(): void
    {
        $firstName = UserNameMother::create('Jhon');
        $lastName = UserNameMother::create('Doe');
        $email = EmailMother::create('jhon.doe@example.com');

        $user = UserMother::create(
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
        );

        $this->assertEquals('jhon', $user->firstName()->value());
        $this->assertEquals('doe', $user->lastName()->value());
        $this->assertEquals('jhon.doe@example.com', $user->email()->value());
        $this->assertEquals(UserStatus::ACTIVE, $user->status());
    }

    public function testItRecordsUserCreatedEventOnCreation(): void
    {
        $user = UserMother::create();
        $events = $user->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserCreated::class, $events[0]);
    }

    public function testItClearsDomainEventsAfterPull(): void
    {
        $user = UserMother::create();
        $user->pullDomainEvents();

        $this->assertEmpty($user->pullDomainEvents());
    }

    public function testItUpdatesName(): void
    {
        $user = UserMother::create();
        $firstName = UserNameMother::create('Jane');
        $lastName = UserNameMother::create('Smith');

        $user->updateName($firstName, $lastName);

        $this->assertEquals('jane', $user->firstName()->value());
        $this->assertEquals('smith', $user->lastName()->value());

        $events = $user->pullDomainEvents();
        $this->assertCount(2, $events);
        $this->assertInstanceOf(UserUpdated::class, $events[1]);
    }
}
