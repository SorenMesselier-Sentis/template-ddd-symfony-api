<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure;

use App\Shared\Domain\ValueObject\Email;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Unit\User\Domain\Mother\EmailMother;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Domain\ValueObject\UserId;
use App\User\Infrastructure\Persistence\Doctrine\Repository\DoctrineUserRepository;

final class DoctrineUserRepositoryTest extends IntegrationTestCase
{
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DoctrineUserRepository($this->em);
    }

    public function test_it_saves_and_finds_a_user(): void
    {
        $user = UserMother::create();

        $this->repository->save($user);

        $found = $this->repository->findById($user->id());

        $this->assertNotNull($found);
        $this->assertTrue($user->id()->equals($found->id()));
    }

    public function test_it_finds_by_email(): void
    {
        $email = EmailMother::create('find.me@example.com');
        $user  = UserMother::create(email: $email);

        $this->repository->save($user);

        $found = $this->repository->findByEmail($email);

        $this->assertNotNull($found);
        $this->assertEquals('find.me@example.com', $found->email()->value());
    }

    public function test_it_returns_null_when_not_found(): void
    {
        $found = $this->repository->findById(UserId::random());

        $this->assertNull($found);
    }

    public function test_it_detects_existing_email(): void
    {
        $email = EmailMother::create('exists@example.com');
        $user  = UserMother::create(email: $email);

        $this->repository->save($user);

        $this->assertTrue($this->repository->existsByEmail($email));
        $this->assertFalse($this->repository->existsByEmail(
            Email::fromString('other@example.com')
        ));
    }

    public function test_it_soft_deletes_a_user(): void
    {
        $user = UserMother::create();
        $this->repository->save($user);

        $user->delete();
        $this->repository->save($user);

        $this->em->clear();

        $found = $this->repository->findById($user->id());
        $this->assertNull($found);
    }
}
