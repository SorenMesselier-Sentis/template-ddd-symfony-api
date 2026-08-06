<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Listener;

use App\Shared\Domain\Exception\IdempotencyKeyConflictException;
use App\Shared\Infrastructure\Http\Listener\IdempotencyKeyListener;
use App\Tests\Unit\UnitTestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class IdempotencyKeyListenerTest extends UnitTestCase
{
    public function testNonPostRequestsAreIgnored(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->never())->method('getItem');

        $listener = $this->createListener($cache);
        $request = Request::create('/api/v1/users', 'GET');
        $request->headers->set('Idempotency-Key', 'key-1');

        $listener->onKernelRequest($this->requestEvent($request));
    }

    public function testNonApiPathsAreIgnored(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->never())->method('getItem');

        $listener = $this->createListener($cache);
        $request = Request::create('/health', 'POST');
        $request->headers->set('Idempotency-Key', 'key-1');

        $listener->onKernelRequest($this->requestEvent($request));
    }

    public function testMissingHeaderIsIgnored(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->never())->method('getItem');

        $listener = $this->createListener($cache);
        $request = Request::create('/api/v1/users', 'POST');

        $listener->onKernelRequest($this->requestEvent($request));
    }

    public function testCacheMissStashesRequestForLaterStorageAndDoesNotSetResponse(): void
    {
        $item = $this->createStub(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);

        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($item);

        $listener = $this->createListener($cache);
        $request = Request::create('/api/v1/users', 'POST', content: '{"a":1}');
        $request->headers->set('Idempotency-Key', 'key-1');
        $event = $this->requestEvent($request);

        $listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
        $this->assertNotNull($request->attributes->get('_idempotency_pending'));
    }

    public function testCacheHitWithMatchingBodyReplaysStoredResponse(): void
    {
        $request = Request::create('/api/v1/users', 'POST', content: '{"a":1}');
        $request->headers->set('Idempotency-Key', 'key-1');
        $bodyHash = hash('sha256', '{"a":1}');

        $item = $this->createStub(CacheItemInterface::class);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn([
            'status' => 201,
            'content' => '{"data":{"id":"1"}}',
            'headers' => ['Content-Type' => 'application/json', 'Location' => '/api/v1/users/1'],
            'request_body_hash' => $bodyHash,
        ]);

        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($item);

        $listener = $this->createListener($cache);
        $event = $this->requestEvent($request);

        $listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"data":{"id":"1"}}', $response->getContent());
        $this->assertSame('/api/v1/users/1', $response->headers->get('Location'));
        $this->assertSame('true', $response->headers->get('Idempotency-Replayed'));
    }

    public function testCacheHitWithDifferentBodyThrowsConflict(): void
    {
        $request = Request::create('/api/v1/users', 'POST', content: '{"a":2}');
        $request->headers->set('Idempotency-Key', 'key-1');

        $item = $this->createStub(CacheItemInterface::class);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn([
            'status' => 201,
            'content' => '{}',
            'headers' => [],
            'request_body_hash' => hash('sha256', '{"a":1}'),
        ]);

        $cache = $this->createStub(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($item);

        $listener = $this->createListener($cache);

        $this->expectException(IdempotencyKeyConflictException::class);
        $listener->onKernelRequest($this->requestEvent($request));
    }

    public function testResponseWithoutPendingAttributeIsNotStored(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->never())->method('save');

        $listener = $this->createListener($cache);
        $request = Request::create('/api/v1/users', 'POST');

        $listener->onKernelResponse($this->responseEvent($request, new Response('{}', 201)));
    }

    public function testUnsuccessfulResponseIsNotStoredEvenWhenPending(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->never())->method('save');

        $listener = $this->createListener($cache);
        $request = Request::create('/api/v1/users', 'POST');
        $request->attributes->set('_idempotency_pending', ['idempotency_abc', 'hash']);

        $listener->onKernelResponse($this->responseEvent($request, new Response('{}', 422)));
    }

    public function testSuccessfulPendingResponseIsStored(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())
            ->method('set')
            ->with($this->callback(function (array $value): bool {
                $this->assertSame(201, $value['status']);
                $this->assertSame('{"data":1}', $value['content']);
                $this->assertSame('/api/v1/users/1', $value['headers']['Location']);
                $this->assertSame('hash-1', $value['request_body_hash']);

                return true;
            }))
            ->willReturnSelf();
        $item->expects($this->once())->method('expiresAfter')->willReturnSelf();

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects($this->once())->method('getItem')->with('idempotency_abc')->willReturn($item);
        $cache->expects($this->once())->method('save')->with($item);

        $listener = $this->createListener($cache);
        $request = Request::create('/api/v1/users', 'POST');
        $request->attributes->set('_idempotency_pending', ['idempotency_abc', 'hash-1']);
        $response = new Response('{"data":1}', 201);
        $response->headers->set('Location', '/api/v1/users/1');

        $listener->onKernelResponse($this->responseEvent($request, $response));
    }

    private function createListener(CacheItemPoolInterface $cache): IdempotencyKeyListener
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        return new IdempotencyKeyListener($cache, $security);
    }

    private function requestEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function responseEvent(Request $request, Response $response): ResponseEvent
    {
        return new ResponseEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}
