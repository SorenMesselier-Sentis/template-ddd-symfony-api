<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use App\Shared\Infrastructure\Http\RequestContext;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Uid\Uuid;

final class RequestCorrelationListener
{
    public function __construct(
        private readonly RequestContext $requestContext,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $inbound = $request->headers->get('X-Request-Id');

        if (\is_string($inbound) && $this->isValidUuidV4($inbound)) {
            $this->requestContext->setRequestId($inbound);

            return;
        }

        $this->requestContext->setRequestId(Uuid::v4()->toRfc4122());
    }

    private function isValidUuidV4(string $value): bool
    {
        if (!Uuid::isValid($value)) {
            return false;
        }

        return 1 === preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $requestId = $this->requestContext->requestId();

        if ('' !== $requestId) {
            $event->getResponse()->headers->set('X-Request-Id', $requestId);
        }
    }
}
