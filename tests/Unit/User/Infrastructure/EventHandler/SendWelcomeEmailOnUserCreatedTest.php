<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventHandler;

use App\Shared\Domain\Email\EmailTemplateRendererInterface;
use App\Shared\Domain\Email\RenderedEmailContent;
use App\Shared\Domain\Exception\EmailDeliveryException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Domain\Notification\NotificationSenderInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\UserCreated;
use App\User\Infrastructure\Email\UserEmailTemplate;
use App\User\Infrastructure\EventHandler\SendWelcomeEmailOnUserCreated;

final class SendWelcomeEmailOnUserCreatedTest extends UnitTestCase
{
    public function testItIncrementsSentMetricOnSuccess(): void
    {
        $sender = $this->createMock(NotificationSenderInterface::class);
        $sender->expects($this->once())->method('send');

        $renderer = $this->createMock(EmailTemplateRendererInterface::class);
        $renderer
            ->expects($this->once())
            ->method('render')
            ->with(UserEmailTemplate::WELCOME, ['firstName' => 'Ada', 'lastName' => 'Lovelace'])
            ->willReturn(new RenderedEmailContent('subject', 'text', '<p>html</p>'));

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('emails_sent_total', ['template' => 'welcome', 'status' => 'sent']);

        $handler = new SendWelcomeEmailOnUserCreated(
            $sender,
            $renderer,
            $this->createStub(LoggerInterface::class),
            $metrics,
        );

        $handler(new UserCreated('user-1', 'Ada', 'Lovelace', 'ada@example.com'));
    }

    public function testItIncrementsFailedMetricAndRethrows(): void
    {
        $sender = $this->createMock(NotificationSenderInterface::class);
        $sender
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new EmailDeliveryException('smtp down'));

        $renderer = $this->createStub(EmailTemplateRendererInterface::class);
        $renderer
            ->method('render')
            ->willReturn(new RenderedEmailContent('subject', 'text', '<p>html</p>'));

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('emails_sent_total', ['template' => 'welcome', 'status' => 'failed']);

        $handler = new SendWelcomeEmailOnUserCreated(
            $sender,
            $renderer,
            $this->createStub(LoggerInterface::class),
            $metrics,
        );

        $this->expectException(EmailDeliveryException::class);

        $handler(new UserCreated('user-1', 'Ada', 'Lovelace', 'ada@example.com'));
    }
}
