<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Controller;

use App\Shared\Infrastructure\Monitoring\Metrics\BuildInfoMetricsInitializer;
use OpenApi\Attributes as OA;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/metrics', name: 'prometheus_metrics', methods: ['GET'])]
#[OA\Get(
    path: '/metrics',
    summary: 'Prometheus metrics',
    description: 'Exposes Prometheus metrics for monitoring and scraping.',
    tags: ['Infrastructure'],
    servers: [new OA\Server(url: 'http://localhost:8080')],
)]
#[OA\Response(
    response: 200,
    description: 'Prometheus metrics',
    content: new OA\MediaType(
        mediaType: 'text/plain',
        example: "# HELP http_requests_total Application metric.\n# TYPE http_requests_total counter\nhttp_requests_total{method=\"GET\",route=\"health_check\",status_code=\"200\"} 1\n",
    ),
)]
final class MetricsController
{
    public function __construct(
        private readonly CollectorRegistry $collectorRegistry,
        private readonly BuildInfoMetricsInitializer $buildInfoInitializer,
    ) {
    }

    public function __invoke(): Response
    {
        $this->buildInfoInitializer->initialize();

        $body = (new RenderTextFormat())->render($this->collectorRegistry->getMetricFamilySamples());

        return new Response(
            $body,
            Response::HTTP_OK,
            [
                'Content-Type' => RenderTextFormat::MIME_TYPE.'; charset=utf-8',
            ],
        );
    }
}
