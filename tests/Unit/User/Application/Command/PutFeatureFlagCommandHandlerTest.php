<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Shared\Domain\FeatureFlag\FeatureFlag;
use App\Shared\Domain\FeatureFlag\FeatureFlagRepositoryInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\PutFeatureFlag\PutFeatureFlagCommand;
use App\User\Application\Command\PutFeatureFlag\PutFeatureFlagCommandHandler;

final class PutFeatureFlagCommandHandlerTest extends UnitTestCase
{
    public function testItSavesTheFlagWithTheGivenState(): void
    {
        $repository = $this->createMock(FeatureFlagRepositoryInterface::class);
        $saved = null;
        $repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (FeatureFlag $flag) use (&$saved): void {
                $saved = $flag;
            });

        $handler = new PutFeatureFlagCommandHandler($repository);
        ($handler)(new PutFeatureFlagCommand(key: 'cursor_pagination', enabled: false, description: 'Kill switch.'));

        $this->assertNotNull($saved);
        $this->assertSame('cursor_pagination', $saved->key);
        $this->assertFalse($saved->enabled);
        $this->assertSame('Kill switch.', $saved->description);
    }
}
