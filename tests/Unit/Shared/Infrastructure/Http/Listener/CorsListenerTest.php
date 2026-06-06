<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Listener;

use App\Shared\Infrastructure\Http\Listener\CorsListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CorsListenerTest extends UnitTestCase
{
    public function testPreflightReturns204WithCorsHeaders(): void
    {
        $listener = new CorsListener('http://localhost:3000', 'GET, POST, OPTIONS');
        $request = Request::create('/api/v1/users', Request::METHOD_OPTIONS);
        $request->headers->set('Origin', 'http://localhost:3000');
        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('http://localhost:3000', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('86400', $response->headers->get('Access-Control-Max-Age'));
    }

    public function testDisallowedOriginOmitsAllowOriginHeader(): void
    {
        $listener = new CorsListener('http://localhost:3000', 'GET, POST, OPTIONS');
        $request = Request::create('/api/v1/users', Request::METHOD_GET);
        $request->headers->set('Origin', 'http://evil.example');
        $response = new Response();
        $event = new ResponseEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testWildcardOrigin(): void
    {
        $listener = new CorsListener('*', 'GET, POST, OPTIONS');
        $request = Request::create('/api/v1/users', Request::METHOD_GET);
        $request->headers->set('Origin', 'http://any.example');
        $response = new Response();
        $event = new ResponseEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $listener->onKernelResponse($event);

        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }
}
