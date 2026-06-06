<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Listener;

use App\Shared\Infrastructure\Http\Listener\ApiHeadersListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiHeadersListenerTest extends UnitTestCase
{
    public function testVersionedApiPathSetsDynamicApiVersionHeader(): void
    {
        $listener = new ApiHeadersListener('dev');
        $response = new Response();
        $event = $this->createResponseEvent('/api/v2/users', $response);

        $listener->onKernelResponse($event);

        $this->assertSame('v2', $response->headers->get('X-API-Version'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
    }

    public function testSwaggerDocPathSetsCspAndFrameOptions(): void
    {
        $listener = new ApiHeadersListener('dev');
        $response = new Response();
        $event = $this->createResponseEvent('/api/doc', $response);

        $listener->onKernelResponse($event);

        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function testProdEnvironmentSetsHstsOnAnyResponse(): void
    {
        $listener = new ApiHeadersListener('prod');
        $response = new Response();
        $event = $this->createResponseEvent('/health', $response);

        $listener->onKernelResponse($event);

        $this->assertStringContainsString('max-age=31536000', (string) $response->headers->get('Strict-Transport-Security'));
    }

    private function createResponseEvent(string $path, Response $response): ResponseEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new ResponseEvent(
            $kernel,
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );
    }
}
