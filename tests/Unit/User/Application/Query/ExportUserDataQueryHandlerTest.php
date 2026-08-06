<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Query;

use App\Shared\Domain\Privacy\PersonalDataExporterInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Query\ExportUserData\ExportUserDataQuery;
use App\User\Application\Query\ExportUserData\ExportUserDataQueryHandler;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserId;

final class ExportUserDataQueryHandlerTest extends UnitTestCase
{
    public function testItAggregatesDataFromEveryRegisteredExporter(): void
    {
        $userId = UserId::random();

        $userContext = $this->createMock(UserContextInterface::class);
        $userContext->expects($this->once())->method('userId')->willReturn($userId);

        $profileExporter = $this->createMock(PersonalDataExporterInterface::class);
        $profileExporter->method('key')->willReturn('profile');
        $profileExporter->expects($this->once())
            ->method('export')
            ->with($userId->value())
            ->willReturn(['id' => $userId->value(), 'email' => 'john@example.com']);

        $documentsExporter = $this->createMock(PersonalDataExporterInterface::class);
        $documentsExporter->method('key')->willReturn('documents');
        $documentsExporter->expects($this->once())
            ->method('export')
            ->with($userId->value())
            ->willReturn([['id' => 'doc-1']]);

        $handler = new ExportUserDataQueryHandler($userContext, [$profileExporter, $documentsExporter]);

        $response = ($handler)(new ExportUserDataQuery());

        $this->assertSame(['id' => $userId->value(), 'email' => 'john@example.com'], $response->data['profile']);
        $this->assertSame([['id' => 'doc-1']], $response->data['documents']);
        $this->assertNotSame('', $response->exportedAt);
    }

    public function testItReturnsEmptyDataWhenNoExportersAreRegistered(): void
    {
        $userContext = $this->createStub(UserContextInterface::class);
        $userContext->method('userId')->willReturn(UserId::random());

        $handler = new ExportUserDataQueryHandler($userContext, []);

        $response = ($handler)(new ExportUserDataQuery());

        $this->assertSame([], $response->data);
    }
}
