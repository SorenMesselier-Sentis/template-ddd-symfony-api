<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\EmailMother;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\Tests\Unit\User\Domain\Mother\UserNameMother;
use App\User\Domain\Event\UserCreated;
use App\User\Domain\Event\UserDeleted;
use App\User\Domain\Event\UserUpdated;
use App\User\Domain\ValueObject\UserStatus;

final class UserTest extends UnitTestCase
{
    public function test_it_creates_a_user_with_correct_attributes(): void
    {
        $firstName = UserNameMother::create('Jhon');
        $lastName = UserNameMother::create('Doe');
        $email = EmailMother::create('jhon.doe@example.com');

        $user = UserMother::create(
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
        );

        $this->assertEquals('Jhon', $user->firstName()->value());
        $this->assertEquals('Doe', $user->lastName()->value());
        $this->assertEquals('jhon.doe@example.com', $user->email()->value());
        $this->assertEquals(UserStatus::ACTIVE, $user->status());
    }

    public function test_it_records_user_created_event_on_creation(): void
    {
        $user = UserMother::create();
        $events = $user->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserCreated::class, $events[0]);
    }

    public function test_it_clears_domain_events_after_pull(): void
    {
        $user = UserMother::create();
        $user->pullDomainEvents();

        $this->assertEmpty($user->pullDomainEvents());
    }

    public function test_it_updates_name(): void
    {
        $user = UserMother::create();
        $firstName = UserNameMother::create('Jane');
        $lastName = UserNameMother::create('Smith');

        $user->updateName($firstName, $lastName);

        $this->assertEquals('Jane', $user->firstName()->value());
        $this->assertEquals('Smith', $user->lastName()->value());

        $events = $user->pullDomainEvents();
        $this->assertCount(2, $events);
        $this->assertInstanceOf(UserUpdated::class, $events[1]);
    }

    public function test_it_soft_deletes(): void
    {
        $user = UserMother::create();
        $user->pullDomainEvents();

        $user->delete();

        $this->assertEquals(UserStatus::DELETED, $user->status());

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserDeleted::class, $events[0]);
    }
}
