<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Listener;

use App\Shared\Domain\Exception\IdempotencyKeyConflictException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Lets clients safely retry a POST by sending the same Idempotency-Key
 * header: the first successful (2xx) response is cached and replayed
 * verbatim on subsequent requests carrying the same key, instead of
 * re-running the command (e.g. re-creating the resource). Reusing a key
 * with a different request body is rejected — see IdempotencyKeyConflictException.
 *
 * Only a best-effort replay cache: there is no distributed lock, so two
 * requests racing with the same brand-new key can both miss the cache and
 * both execute — this covers "retry after a timeout", not concurrent
 * double-submission.
 */
final class IdempotencyKeyListener
{
    private const string HEADER = 'Idempotency-Key';
    private const string REQUEST_ATTRIBUTE = '_idempotency_pending';
    private const int TTL_SECONDS = 86400;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly Security $security,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isMethod(Request::METHOD_POST)) {
            return;
        }

        if (1 !== preg_match('#^/api/v\d+#', $request->getPathInfo())) {
            return;
        }

        $idempotencyKey = $request->headers->get(self::HEADER);

        if (!\is_string($idempotencyKey) || '' === trim($idempotencyKey)) {
            return;
        }

        $cacheKey = $this->cacheKey($request, $idempotencyKey);
        $bodyHash = hash('sha256', $request->getContent());
        $item = $this->cache->getItem($cacheKey);

        if (!$item->isHit()) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE, [$cacheKey, $bodyHash]);

            return;
        }

        /** @var array{status: int, content: string, headers: array<string, string>, request_body_hash: string} $stored */
        $stored = $item->get();

        if ($stored['request_body_hash'] !== $bodyHash) {
            throw IdempotencyKeyConflictException::create($idempotencyKey);
        }

        $response = new Response($stored['content'], $stored['status'], $stored['headers']);
        $response->headers->set('Idempotency-Replayed', 'true');
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        /** @var array{0: string, 1: string}|null $pending */
        $pending = $request->attributes->get(self::REQUEST_ATTRIBUTE);

        if (null === $pending) {
            return;
        }

        $response = $event->getResponse();

        if (!$response->isSuccessful()) {
            return;
        }

        [$cacheKey, $bodyHash] = $pending;

        $headers = [];
        foreach (['Content-Type', 'Location'] as $name) {
            $value = $response->headers->get($name);

            if (null !== $value) {
                $headers[$name] = $value;
            }
        }

        $item = $this->cache->getItem($cacheKey);
        $item->set([
            'status' => $response->getStatusCode(),
            'content' => (string) $response->getContent(),
            'headers' => $headers,
            'request_body_hash' => $bodyHash,
        ]);
        $item->expiresAfter(self::TTL_SECONDS);
        $this->cache->save($item);
    }

    private function cacheKey(Request $request, string $idempotencyKey): string
    {
        $actor = $this->security->getUser()?->getUserIdentifier() ?? $request->getClientIp() ?? 'anonymous';

        return 'idempotency_'.hash('sha256', $actor.'|'.$request->getPathInfo().'|'.$idempotencyKey);
    }
}
