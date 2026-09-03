<?php

declare(strict_types=1);

namespace App\Tests\Unit\ApiClient\Application;

use App\ApiClient\Application\Command\CreateApiClient\CreateApiClientCommand;
use App\ApiClient\Application\Command\CreateApiClient\CreateApiClientCommandHandler;
use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateApiClientCommandHandlerTest extends UnitTestCase
{
    public function testItCreatesAnApiClientAndReturnsThePlainSecretOnce(): void
    {
        /** @var ApiClientRepositoryInterface&MockObject $repository */
        $repository = $this->createMock(ApiClientRepositoryInterface::class);
        /** @var EventBusInterface&MockObject $eventBus */
        $eventBus = $this->createMock(EventBusInterface::class);
        $logger = $this->createStub(LoggerInterface::class);

        $handler = new CreateApiClientCommandHandler($repository, $eventBus, $logger);

        $id = ApiClientId::random()->value();
        $command = new CreateApiClientCommand(id: $id, name: 'Billing worker', scopes: ['documents:write']);

        $repository->expects($this->once())->method('save');
        $eventBus->expects($this->once())->method('publish');

        $result = ($handler)($command);

        $this->assertSame($id, $result['id']);
        $this->assertNotSame('', $result['secret']);
        $this->assertSame(64, \strlen($result['secret']));
    }
}
