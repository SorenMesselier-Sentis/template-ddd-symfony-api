<?php

declare(strict_types=1);

namespace App\Tests\Unit\ApiClient\Application;

use App\ApiClient\Application\Command\RotateApiClientSecret\RotateApiClientSecretCommand;
use App\ApiClient\Application\Command\RotateApiClientSecret\RotateApiClientSecretCommandHandler;
use App\ApiClient\Domain\Exception\ApiClientNotFoundException;
use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use App\ApiClient\Domain\Repository\IssuedAccessTokenRepositoryInterface;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\ApiClient\Domain\Mother\ApiClientMother;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class RotateApiClientSecretCommandHandlerTest extends UnitTestCase
{
    private ApiClientRepositoryInterface&MockObject $repository;
    private IssuedAccessTokenRepositoryInterface&MockObject $tokenRepository;
    private RotateApiClientSecretCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ApiClientRepositoryInterface::class);
        $this->tokenRepository = $this->createMock(IssuedAccessTokenRepositoryInterface::class);

        $this->handler = new RotateApiClientSecretCommandHandler(
            $this->repository,
            $this->tokenRepository,
            $this->createStub(EventBusInterface::class),
            $this->createStub(LoggerInterface::class),
        );
    }

    public function testItRotatesTheSecretAndRevokesExistingTokens(): void
    {
        $entity = ApiClientMother::create();
        $oldHash = $entity->secretHash();
        $command = new RotateApiClientSecretCommand(id: $entity->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->once())->method('save');
        $this->tokenRepository->expects($this->once())
            ->method('revokeAllForClient')
            ->with($entity->id()->value());

        $result = ($this->handler)($command);

        $this->assertNotSame('', $result['secret']);
        $this->assertTrue($entity->secretHash()->verify($result['secret']));
        $this->assertFalse($oldHash->verify($result['secret']));
    }

    public function testItThrowsWhenTheClientIsNotFound(): void
    {
        $this->expectException(ApiClientNotFoundException::class);

        $this->repository->expects($this->once())->method('findById')->willReturn(null);
        $this->tokenRepository->expects($this->never())->method('revokeAllForClient');

        ($this->handler)(new RotateApiClientSecretCommand(id: ApiClientId::random()->value()));
    }
}
