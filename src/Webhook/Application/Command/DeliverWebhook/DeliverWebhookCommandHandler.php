<?php

declare(strict_types=1);

namespace App\Webhook\Application\Command\DeliverWebhook;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use App\Webhook\Domain\ValueObject\WebhookSubscriptionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Throws on anything but a 2xx response so the webhook_delivery transport's retry_strategy
 * (messenger.yaml — more tolerant than the default `async` transport, since a third party being
 * slow/down is the expected case here, not a bug) retries with backoff, eventually landing in
 * async.dead_letter after the retry budget is exhausted.
 */
#[AsMessageHandler(bus: 'command.bus')]
final class DeliverWebhookCommandHandler
{
    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly WebhookSubscriptionRepositoryInterface $repository,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeliverWebhookCommand $command): void
    {
        $subscription = $this->repository->findById(WebhookSubscriptionId::fromString($command->subscriptionId));

        if (null === $subscription || !$subscription->isActive()) {
            // Disabled/deleted since DispatchWebhooksOnAnyDomainEvent dispatched this — not a
            // delivery failure, just nothing left to deliver to.
            return;
        }

        $body = json_encode([
            'id' => $command->eventId,
            'event' => $command->eventName,
            'occurred_at' => $command->occurredOn,
            'data' => $command->payload,
        ], \JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $body, $subscription->secret());

        try {
            $statusCode = $this->httpClient->request('POST', $subscription->url()->value(), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Id' => $command->eventId,
                    'X-Webhook-Event' => $command->eventName,
                    'X-Webhook-Signature' => 'sha256='.$signature,
                ],
                'body' => $body,
                'timeout' => self::TIMEOUT_SECONDS,
            ])->getStatusCode();
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Webhook delivery failed (transport error)', [
                'exception' => $exception,
                'subscriptionId' => $command->subscriptionId,
                'event' => $command->eventName,
            ]);

            throw $exception;
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger->error('Webhook delivery failed (non-2xx response)', [
                'subscriptionId' => $command->subscriptionId,
                'event' => $command->eventName,
                'statusCode' => $statusCode,
            ]);

            throw new \RuntimeException(sprintf('Webhook delivery to subscription "%s" failed with status %d.', $command->subscriptionId, $statusCode));
        }

        $this->logger->info('Webhook delivered', [
            'subscriptionId' => $command->subscriptionId,
            'event' => $command->eventName,
            'statusCode' => $statusCode,
        ]);
    }
}
