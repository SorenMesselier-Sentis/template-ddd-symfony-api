<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Monitoring\Metrics;

use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Monitoring\Metrics\HttpMetricsListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class HttpMetricsListenerTest extends UnitTestCase
{
    public function testItRecordsRequestMetricsForNormalRoutes(): void
    {
        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with(
                'http_requests_total',
                [
                    'method' => 'GET',
                    'route' => 'app_users',
                    'status_code' => '200',
                ],
            );

        $metrics
            ->expects($this->once())
            ->method('observeHistogram')
            ->with(
                'http_request_duration_seconds',
                $this->greaterThanOrEqual(0.0),
                [
                    'method' => 'GET',
                    'route' => 'app_users',
                    'status_code' => '200',
                ],
                self::callback(static fn (mixed $buckets): bool => \is_array($buckets)
                    && 11 === \count($buckets)),
            );

        $listener = new HttpMetricsListener($metrics);
        $kernel = $this->createStub(HttpKernelInterface::class);

        $request = Request::create('/api/v1/users');
        $request->attributes->set('_route', 'app_users');

        $listener->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $listener->onKernelTerminate(new TerminateEvent($kernel, $request, new Response('', 200)));
    }

    public function testItUsesUnmatchedWhenRouteMissing(): void
    {
        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('http_requests_total', $this->callback(static fn (array $labels): bool => '_unmatched' === $labels['route']));

        $listener = new HttpMetricsListener($metrics);
        $kernel = $this->createStub(HttpKernelInterface::class);

        $request = Request::create('/no-such-resource');

        $listener->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $listener->onKernelTerminate(new TerminateEvent($kernel, $request, new Response('', 404)));
    }

    public function testItDoesNotObserveMetricsWhenRouteIsExcluded(): void
    {
        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics->expects($this->never())->method('incrementCounter');
        $metrics->expects($this->never())->method('observeHistogram');

        $listener = new HttpMetricsListener($metrics);
        $kernel = $this->createStub(HttpKernelInterface::class);

        $request = Request::create('/metrics');
        $request->attributes->set('_route', 'prometheus_metrics');

        $listener->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $listener->onKernelTerminate(new TerminateEvent($kernel, $request, new Response('', 200)));
    }

    public function testItDoesNotObserveSubRequests(): void
    {
        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics->expects($this->never())->method('incrementCounter');

        $listener = new HttpMetricsListener($metrics);
        $kernel = $this->createStub(HttpKernelInterface::class);

        $request = Request::create('/');
        $request->attributes->set('_route', 'home');

        $listener->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST));
        $listener->onKernelTerminate(new TerminateEvent($kernel, $request, new Response()));
    }
}
