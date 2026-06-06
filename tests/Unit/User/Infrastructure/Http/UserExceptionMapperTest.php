<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Http;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\InsufficientPrivilegesException;
use App\User\Domain\Exception\InvalidTokenException;
use App\User\Domain\Exception\MissingTokenException;
use App\User\Domain\Exception\TokenExpiredException;
use App\User\Domain\Exception\TokenRevokedException;
use App\User\Infrastructure\Http\UserExceptionMapper;

final class UserExceptionMapperTest extends UnitTestCase
{
    private UserExceptionMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new UserExceptionMapper();
    }

    public function testInsufficientPrivilegesMapsTo403(): void
    {
        [$status, $code] = $this->mapper->resolve(InsufficientPrivilegesException::create());

        $this->assertSame(403, $status);
        $this->assertSame('insufficient_privileges', $code);
    }

    public function testInvalidTokenMapsTo401(): void
    {
        [$status, $code] = $this->mapper->resolve(InvalidTokenException::create());

        $this->assertSame(401, $status);
        $this->assertSame($this->mapper->resolve(InvalidTokenException::create())[1], $code);
    }

    public function testMissingTokenMapsTo401(): void
    {
        [$status] = $this->mapper->resolve(MissingTokenException::create());

        $this->assertSame(401, $status);
    }

    public function testTokenExpiredMapsTo401(): void
    {
        [$status] = $this->mapper->resolve(TokenExpiredException::create());

        $this->assertSame(401, $status);
    }

    public function testTokenRevokedMapsTo401(): void
    {
        [$status] = $this->mapper->resolve(TokenRevokedException::create());

        $this->assertSame(401, $status);
    }
}
