<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Privacy;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Privacy\UserPersonalDataExporter;
use App\User\Domain\Repository\ConsentRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;

final class UserPersonalDataExporterTest extends UnitTestCase
{
    public function testKeyIsProfile(): void
    {
        $exporter = new UserPersonalDataExporter(
            $this->createStub(UserRepositoryInterface::class),
            $this->createStub(ConsentRepositoryInterface::class),
        );

        $this->assertSame('profile', $exporter->key());
    }

    public function testExportReturnsUserFields(): void
    {
        $user = UserMother::create();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with($this->callback(fn (UserId $id) => $id->equals($user->id())))
            ->willReturn($user);

        $consentRepository = $this->createStub(ConsentRepositoryInterface::class);
        $consentRepository->method('findByUserId')->willReturn([]);

        $exporter = new UserPersonalDataExporter($repository, $consentRepository);
        $data = $exporter->export($user->id()->value());

        $this->assertSame($user->id()->value(), $data['id']);
        $this->assertSame($user->email()->value(), $data['email']);
        $this->assertSame($user->firstName()->value(), $data['first_name']);
        $this->assertSame($user->lastName()->value(), $data['last_name']);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertSame([], $data['consents']);
    }

    public function testExportReturnsEmptyArrayWhenUserNotFound(): void
    {
        $repository = $this->createStub(UserRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);

        $exporter = new UserPersonalDataExporter(
            $repository,
            $this->createStub(ConsentRepositoryInterface::class),
        );

        $this->assertSame([], $exporter->export(UserId::random()->value()));
    }
}
