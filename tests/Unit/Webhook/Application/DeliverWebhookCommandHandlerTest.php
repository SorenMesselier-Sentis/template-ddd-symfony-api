<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Application;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\Webhook\Domain\Mother\WebhookSubscriptionMother;
use App\Webhook\Application\Command\DeliverWebhook\DeliverWebhookCommand;
use App\Webhook\Application\Command\DeliverWebhook\DeliverWebhookCommandHandler;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class DeliverWebhookCommandHandlerTest extends UnitTestCase
{
    private WebhookSubscriptionRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(WebhookSubscriptionRepositoryInterface::class);
    }

    public function testItSignsAndDeliversTheEventPayload(): void
    {
        $subscription = WebhookSubscriptionMother::create(secret: 'shared-secret');
        $this->repository->method('findById')->willReturn($subscription);

        $command = new DeliverWebhookCommand(
            subscriptionId: $subscription->id()->value(),
            eventId: 'event-id',
            eventName: 'document.uploaded',
            occurredOn: '2026-09-01T12:00:00+00:00',
            payload: ['ownerId' => 'owner-id'],
        );

        $expectedBody = json_encode([
            'id' => 'event-id',
            'event' => 'document.uploaded',
            'occurred_at' => '2026-09-01T12:00:00+00:00',
            'data' => ['ownerId' => 'owner-id'],
        ], \JSON_THROW_ON_ERROR);
        $expectedSignature = hash_hmac('sha256', $expectedBody, 'shared-secret');

        $capturedMethod = null;
        $capturedUrl = null;
        $capturedOptions = null;

        $httpClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$capturedMethod, &$capturedUrl, &$capturedOptions) {
                $capturedMethod = $method;
                $capturedUrl = $url;
                $capturedOptions = $options;

                return new MockResponse('', ['http_code' => 200]);
            },
        );

        $handler = new DeliverWebhookCommandHandler($this->repository, $httpClient, $this->createStub(LoggerInterface::class));

        ($handler)($command);

        $this->assertSame('POST', $capturedMethod);
        $this->assertSame($subscription->url()->value(), $capturedUrl);
        $this->assertSame($expectedBody, $capturedOptions['body']);
        $this->assertContains('X-Webhook-Signature: sha256='.$expectedSignature, $capturedOptions['headers']);
    }

    public function testItThrowsOnANon2xxResponseSoTheTransportRetries(): void
    {
        $subscription = WebhookSubscriptionMother::create();
        $this->repository->method('findById')->willReturn($subscription);

        $httpClient = new MockHttpClient(fn () => new MockResponse('', ['http_code' => 500]));
        $handler = new DeliverWebhookCommandHandler($this->repository, $httpClient, $this->createStub(LoggerInterface::class));

        $this->expectException(\RuntimeException::class);

        ($handler)(new DeliverWebhookCommand(
            subscriptionId: $subscription->id()->value(),
            eventId: 'event-id',
            eventName: 'document.uploaded',
            occurredOn: '2026-09-01T12:00:00+00:00',
            payload: [],
        ));
    }

    public function testItPropagatesATransportException(): void
    {
        $subscription = WebhookSubscriptionMother::create();
        $this->repository->method('findById')->willReturn($subscription);

        $httpClient = new MockHttpClient(function () {
            throw new TransportException('Connection refused');
        });
        $handler = new DeliverWebhookCommandHandler($this->repository, $httpClient, $this->createStub(LoggerInterface::class));

        $this->expectException(TransportException::class);

        ($handler)(new DeliverWebhookCommand(
            subscriptionId: $subscription->id()->value(),
            eventId: 'event-id',
            eventName: 'document.uploaded',
            occurredOn: '2026-09-01T12:00:00+00:00',
            payload: [],
        ));
    }

    public function testItIsANoOpWhenTheSubscriptionNoLongerExists(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $httpClient = new MockHttpClient(function () {
            self::fail('The HTTP client should never be called when the subscription is gone.');
        });
        $handler = new DeliverWebhookCommandHandler($this->repository, $httpClient, $this->createStub(LoggerInterface::class));

        ($handler)(new DeliverWebhookCommand(
            subscriptionId: WebhookSubscriptionId::random()->value(),
            eventId: 'event-id',
            eventName: 'document.uploaded',
            occurredOn: '2026-09-01T12:00:00+00:00',
            payload: [],
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItIsANoOpWhenTheSubscriptionIsDisabled(): void
    {
        $subscription = WebhookSubscriptionMother::create();
        $subscription->disable();
        $this->repository->method('findById')->willReturn($subscription);

        $httpClient = new MockHttpClient(function () {
            self::fail('The HTTP client should never be called for a disabled subscription.');
        });
        $handler = new DeliverWebhookCommandHandler($this->repository, $httpClient, $this->createStub(LoggerInterface::class));

        ($handler)(new DeliverWebhookCommand(
            subscriptionId: $subscription->id()->value(),
            eventId: 'event-id',
            eventName: 'document.uploaded',
            occurredOn: '2026-09-01T12:00:00+00:00',
            payload: [],
        ));

        $this->expectNotToPerformAssertions();
    }
}
