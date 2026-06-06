<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Listener;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\ValidationError;
use App\Shared\Domain\Exception\ValidationException;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Infrastructure\Http\ExceptionMapperInterface;
use App\Shared\Infrastructure\Http\Listener\ExceptionListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ExceptionListenerTest extends UnitTestCase
{
    public function testUnmappedDomainExceptionFallsBackTo422(): void
    {
        $exception = new class ('custom') extends DomainException {
            public function errorCode(): string
            {
                return 'custom.error';
            }
        };

        $listener = new ExceptionListener($this->createStub(LoggerInterface::class), []);
        [$status, $code] = $listener->resolveException($exception);

        $this->assertSame(422, $status);
        $this->assertSame('domain_error', $code);
    }

    public function testFirstMatchingMapperWins(): void
    {
        $exception = new \RuntimeException('mapped');

        $mapper = new class implements ExceptionMapperInterface {
            public function supports(\Throwable $exception): bool
            {
                return $exception instanceof \RuntimeException;
            }

            public function resolve(\Throwable $exception): array
            {
                return [418, 'teapot'];
            }
        };

        $listener = new ExceptionListener($this->createStub(LoggerInterface::class), [$mapper]);
        [$status, $code] = $listener->resolveException($exception);

        $this->assertSame(418, $status);
        $this->assertSame('teapot', $code);
    }

    public function testValidationExceptionResponseIncludesErrors(): void
    {
        $exception = new ValidationException([
            new ValidationError('email', 'required', 'Field "email" is required.'),
        ]);

        $listener = new ExceptionListener($this->createStub(LoggerInterface::class), []);
        $kernel = $this->createStub(HttpKernelInterface::class);
        $event = new ExceptionEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('validation_error', $payload['error']['code']);
        $this->assertCount(1, $payload['error']['errors']);
    }

    public function testPlainExceptionReturns500(): void
    {
        $listener = new ExceptionListener($this->createStub(LoggerInterface::class), []);
        [$status, $code] = $listener->resolveException(new \Exception('boom'));

        $this->assertSame(500, $status);
        $this->assertSame('internal_server_error', $code);
    }

    // Feature: api-platform-improvements, Property 1: Unhandled DomainException subclasses fall back to 422
    public function testPropertyUnhandledDomainExceptionFallsBackTo422(): void
    {
        $listener = new ExceptionListener($this->createStub(LoggerInterface::class), []);

        for ($i = 0; $i < 100; ++$i) {
            $exception = new class ('msg-'.$i) extends DomainException {
                public function errorCode(): string
                {
                    return 'custom_'.$this->getMessage();
                }
            };

            [$status, $code] = $listener->resolveException($exception);
            $this->assertSame(422, $status);
            $this->assertSame('domain_error', $code);
        }
    }
}
