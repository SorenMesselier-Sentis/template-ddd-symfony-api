<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Metrics;

use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Automatic HTTP metrics;.
 *
 * See https://prometheus.io/docs/practices/naming/#metric-names — durations in seconds.
 */
final class HttpMetricsListener
{
    private const string METRIC_HTTP_REQUESTS_TOTAL = 'http_requests_total';

    private const string METRIC_HTTP_REQUEST_DURATION_SECONDS = 'http_request_duration_seconds';

    private const string REQUEST_ATTRIBUTE_STARTED_AT = '_http_metrics_started_at';

    /**
     * Standard Prometheus histogram buckets for request latency (seconds).
     *
     * @var list<float>
     */
    private const array HTTP_REQUEST_DURATION_BUCKETS = [
        0.005,
        0.01,
        0.025,
        0.05,
        0.1,
        0.25,
        0.5,
        1.0,
        2.5,
        5.0,
        10.0,
    ];

    public function __construct(
        private readonly MetricsCollectorInterface $metrics,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->shouldSkipObservation($request)) {
            return;
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE_STARTED_AT, microtime(true));
    }

    #[AsEventListener(event: KernelEvents::TERMINATE)]
    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->shouldSkipObservation($request)) {
            return;
        }

        if (!$request->attributes->has(self::REQUEST_ATTRIBUTE_STARTED_AT)) {
            return;
        }

        $startedAt = $request->attributes->get(self::REQUEST_ATTRIBUTE_STARTED_AT);
        if (!\is_float($startedAt)) {
            return;
        }

        $labels = [
            'method' => $request->getMethod(),
            'route' => $this->resolveRouteLabel($request),
            'status_code' => (string) ($event->getResponse()?->getStatusCode() ?? 500),
        ];

        $this->metrics->incrementCounter(self::METRIC_HTTP_REQUESTS_TOTAL, $labels);

        $this->metrics->observeHistogram(
            self::METRIC_HTTP_REQUEST_DURATION_SECONDS,
            microtime(true) - $startedAt,
            $labels,
            self::HTTP_REQUEST_DURATION_BUCKETS,
        );
    }

    private function shouldSkipObservation(Request $request): bool
    {
        $route = $request->attributes->get('_route');

        return \is_string($route) && \in_array($route, ['prometheus_metrics', 'health_liveness'], true);
    }

    private function resolveRouteLabel(Request $request): string
    {
        $route = $request->attributes->get('_route');

        return \is_string($route) && '' !== $route ? $route : '_unmatched';
    }
}
