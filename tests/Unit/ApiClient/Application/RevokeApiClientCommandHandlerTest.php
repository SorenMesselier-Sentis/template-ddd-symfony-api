<?php

declare(strict_types=1);

namespace App\Tests\Unit\ApiClient\Application;

use App\ApiClient\Application\Command\RevokeApiClient\RevokeApiClientCommand;
use App\ApiClient\Application\Command\RevokeApiClient\RevokeApiClientCommandHandler;
use App\ApiClient\Domain\Exception\ApiClientNotFoundException;
use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use App\ApiClient\Domain\Repository\IssuedAccessTokenRepositoryInterface;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\ApiClient\Domain\ValueObject\ApiClientStatus;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\ApiClient\Domain\Mother\ApiClientMother;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class RevokeApiClientCommandHandlerTest extends UnitTestCase
{
    private ApiClientRepositoryInterface&MockObject $repository;
    private IssuedAccessTokenRepositoryInterface&MockObject $tokenRepository;
    private RevokeApiClientCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ApiClientRepositoryInterface::class);
        $this->tokenRepository = $this->createMock(IssuedAccessTokenRepositoryInterface::class);

        $this->handler = new RevokeApiClientCommandHandler(
            $this->repository,
            $this->tokenRepository,
            $this->createStub(EventBusInterface::class),
            $this->createStub(LoggerInterface::class),
        );
    }

    public function testItRevokesTheClientAndAllItsIssuedTokens(): void
    {
        $entity = ApiClientMother::create();
        $command = new RevokeApiClientCommand(id: $entity->id()->value());

        $this->repository->expects($this->once())->method('findById')->willReturn($entity);
        $this->repository->expects($this->once())->method('save');
        $this->tokenRepository->expects($this->once())
            ->method('revokeAllForClient')
            ->with($entity->id()->value());

        ($this->handler)($command);

        $this->assertSame(ApiClientStatus::REVOKED, $entity->status());
    }

    public function testItThrowsWhenTheClientIsNotFound(): void
    {
        $this->expectException(ApiClientNotFoundException::class);

        $this->repository->expects($this->once())->method('findById')->willReturn(null);
        $this->tokenRepository->expects($this->never())->method('revokeAllForClient');

        ($this->handler)(new RevokeApiClientCommand(id: ApiClientId::random()->value()));
    }
}
