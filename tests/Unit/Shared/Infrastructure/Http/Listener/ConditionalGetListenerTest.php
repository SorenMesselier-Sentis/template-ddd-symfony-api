<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Listener;

use App\Shared\Infrastructure\Http\Listener\ConditionalGetListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ConditionalGetListenerTest extends UnitTestCase
{
    public function testSetsStrongEtagAndCacheControlOnSuccessfulGetApiResponse(): void
    {
        $listener = new ConditionalGetListener();
        $event = $this->createEvent('GET', '/api/v1/users', new JsonResponse(['data' => ['id' => '1']]));

        $listener->onKernelResponse($event);

        $response = $event->getResponse();
        $this->assertMatchesRegularExpression('/^"[a-f0-9]{32}"$/', (string) $response->headers->get('ETag'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-cache'));
    }

    public function testReturns304WhenIfNoneMatchMatchesCurrentEtag(): void
    {
        $listener = new ConditionalGetListener();
        $body = json_encode(['data' => ['id' => '1']]);
        $etag = '"'.md5((string) $body).'"';

        $request = Request::create('/api/v1/users', 'GET');
        $request->headers->set('If-None-Match', $etag);
        $event = new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new JsonResponse(['data' => ['id' => '1']]),
        );

        $listener->onKernelResponse($event);

        $response = $event->getResponse();
        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $this->assertFalse($response->headers->has('Content-Type'));
    }

    public function testNonGetRequestsAreNotEtagged(): void
    {
        $listener = new ConditionalGetListener();
        $event = $this->createEvent('POST', '/api/v1/users', new JsonResponse(['data' => ['id' => '1']], 201));

        $listener->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('ETag'));
    }

    public function testNonApiPathsAreNotEtagged(): void
    {
        $listener = new ConditionalGetListener();
        $event = $this->createEvent('GET', '/health', new JsonResponse(['status' => 'ok']));

        $listener->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('ETag'));
    }

    public function testUnsuccessfulResponsesAreNotEtagged(): void
    {
        $listener = new ConditionalGetListener();
        $event = $this->createEvent('GET', '/api/v1/users/missing', new JsonResponse(['error' => ['code' => 'user.not_found']], 404));

        $listener->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('ETag'));
    }

    public function testEmptyBodyResponsesAreNotEtagged(): void
    {
        $listener = new ConditionalGetListener();
        $event = $this->createEvent('GET', '/api/v1/users/1', new Response(status: 204));

        $listener->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('ETag'));
    }

    private function createEvent(string $method, string $path, Response $response): ResponseEvent
    {
        return new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path, $method),
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );
    }
}
