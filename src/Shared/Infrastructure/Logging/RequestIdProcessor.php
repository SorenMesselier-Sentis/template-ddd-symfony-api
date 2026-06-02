<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use App\Shared\Infrastructure\Http\RequestContext;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class RequestIdProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestContext $requestContext,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $requestId = $this->requestContext->requestId();

        if ('' === $requestId) {
            return $record;
        }

        return $record->with(extra: array_merge($record->extra, ['request_id' => $requestId]));
    }
}
