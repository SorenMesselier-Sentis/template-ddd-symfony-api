<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Shared\Domain\Privacy\PersonalDataEraserInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\EraseMyData\EraseMyDataCommand;
use App\User\Application\Command\EraseMyData\EraseMyDataCommandHandler;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\MockObject\MockObject;

final class EraseMyDataCommandHandlerTest extends UnitTestCase
{
    public function testItInvokesEveryTaggedEraserWithTheCommandId(): void
    {
        $id = UserId::random()->value();

        /** @var PersonalDataEraserInterface&MockObject $eraserA */
        $eraserA = $this->createMock(PersonalDataEraserInterface::class);
        /** @var PersonalDataEraserInterface&MockObject $eraserB */
        $eraserB = $this->createMock(PersonalDataEraserInterface::class);

        $eraserA->expects($this->once())->method('erase')->with($id);
        $eraserB->expects($this->once())->method('erase')->with($id);

        $handler = new EraseMyDataCommandHandler([$eraserA, $eraserB]);
        ($handler)(new EraseMyDataCommand($id));
    }
}
