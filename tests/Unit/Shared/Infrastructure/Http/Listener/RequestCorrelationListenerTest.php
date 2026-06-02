<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Listener;

use App\Shared\Infrastructure\Http\Listener\RequestCorrelationListener;
use App\Shared\Infrastructure\Http\RequestContext;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Uid\Uuid;

final class RequestCorrelationListenerTest extends UnitTestCase
{
    public function testValidInboundRequestIdIsPreserved(): void
    {
        $context = new RequestContext();
        $listener = new RequestCorrelationListener($context);
        $requestId = Uuid::v4()->toRfc4122();
        $request = Request::create('/');
        $request->headers->set('X-Request-Id', $requestId);

        $listener->onKernelRequest($this->requestEvent($request));
        $response = new Response();
        $listener->onKernelResponse($this->responseEvent($request, $response));

        $this->assertSame($requestId, $context->requestId());
        $this->assertSame($requestId, $response->headers->get('X-Request-Id'));
    }

    public function testInvalidInboundRequestIdIsReplaced(): void
    {
        $context = new RequestContext();
        $listener = new RequestCorrelationListener($context);
        $request = Request::create('/');
        $request->headers->set('X-Request-Id', 'not-a-uuid');

        $listener->onKernelRequest($this->requestEvent($request));
        $response = new Response();
        $listener->onKernelResponse($this->responseEvent($request, $response));

        $this->assertTrue(Uuid::isValid($context->requestId()));
        $this->assertNotSame('not-a-uuid', $response->headers->get('X-Request-Id'));
    }

    private function requestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }

    private function responseEvent(Request $request, Response $response): ResponseEvent
    {
        return new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );
    }
}
