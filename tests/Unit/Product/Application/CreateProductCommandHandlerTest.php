<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Product\Application\Command\CreateProduct\CreateProductCommand;
use App\Product\Application\Command\CreateProduct\CreateProductCommandHandler;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductId;

final class CreateProductCommandHandlerTest extends UnitTestCase
{
    private ProductRepositoryInterface $repository;
    private EventBusInterface           $eventBus;
    private LoggerInterface             $logger;
    private CreateProductCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductRepositoryInterface::class);
        $this->eventBus   = $this->createMock(EventBusInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->handler = new CreateProductCommandHandler(
            $this->repository,
            $this->eventBus,
            $this->logger,
        );
    }

    public function test_it_creates_a_product(): void
    {
        $command = new CreateProductCommand(id: ProductId::random()->value());

        $this->repository->expects($this->once())->method('save');
        $this->eventBus->expects($this->once())->method('publish');

        ($this->handler)($command);
    }
}