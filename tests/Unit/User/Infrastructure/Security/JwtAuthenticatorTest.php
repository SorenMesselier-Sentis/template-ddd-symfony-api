<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Security;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Service\TokenServiceInterface;
use App\User\Infrastructure\Security\JwtAuthenticator;
use App\User\Infrastructure\Security\PublicApiRequestMatcher;
use Symfony\Component\HttpFoundation\Request;

final class JwtAuthenticatorTest extends UnitTestCase
{
    private JwtAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->authenticator = new JwtAuthenticator(
            $this->createStub(TokenServiceInterface::class),
            new PublicApiRequestMatcher(),
        );
    }

    public function testItDoesNotAuthenticatePublicDocRoutesEvenWithAuthorizationHeader(): void
    {
        $request = Request::create('/api/doc', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid-token');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testItDoesNotAuthenticatePublicDocJsonRoute(): void
    {
        $request = Request::create('/api/doc.json', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid-token');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testItAuthenticatesProtectedRoutesWhenAuthorizationHeaderIsPresent(): void
    {
        $request = Request::create('/api/v1/users', 'GET');
        $request->headers->set('Authorization', 'Bearer some-token');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testItAuthenticatesProtectedRoutesEvenWithoutAuthorizationHeader(): void
    {
        $request = Request::create('/api/v1/users', 'GET');

        $this->assertTrue($this->authenticator->supports($request));
    }
}
