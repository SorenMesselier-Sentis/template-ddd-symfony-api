<?php

declare(strict_types=1);

namespace App\Tests\Unit\Webhook\Domain\ValueObject;

use App\Tests\Unit\UnitTestCase;
use App\Webhook\Domain\Exception\InvalidWebhookUrlException;
use App\Webhook\Domain\ValueObject\WebhookUrl;
use PHPUnit\Framework\Attributes\DataProvider;

final class WebhookUrlTest extends UnitTestCase
{
    public function testItAcceptsAValidPublicHttpsUrl(): void
    {
        $url = WebhookUrl::fromString('https://example.com/webhooks/inbound');

        $this->assertSame('https://example.com/webhooks/inbound', $url->value());
    }

    public function testItRejectsAMalformedUrl(): void
    {
        $this->expectException(InvalidWebhookUrlException::class);

        WebhookUrl::fromString('not a url');
    }

    public function testItRejectsPlainHttp(): void
    {
        $this->expectException(InvalidWebhookUrlException::class);

        WebhookUrl::fromString('http://example.com/webhooks/inbound');
    }

    public function testItRejectsLocalhost(): void
    {
        $this->expectException(InvalidWebhookUrlException::class);

        WebhookUrl::fromString('https://localhost/webhooks/inbound');
    }

    public function testItRejectsADotLocalHostname(): void
    {
        $this->expectException(InvalidWebhookUrlException::class);

        WebhookUrl::fromString('https://my-service.local/inbound');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function blockedIpProvider(): iterable
    {
        yield 'loopback' => ['127.0.0.1'];
        yield 'private class A' => ['10.0.0.1'];
        yield 'private class B' => ['172.16.0.1'];
        yield 'private class C' => ['192.168.1.1'];
        yield 'link-local / cloud metadata' => ['169.254.169.254'];
    }

    #[DataProvider('blockedIpProvider')]
    public function testItRejectsPrivateAndReservedIpLiterals(string $ip): void
    {
        $this->expectException(InvalidWebhookUrlException::class);

        WebhookUrl::fromString(sprintf('https://%s/inbound', $ip));
    }

    public function testItAcceptsAPublicIpLiteral(): void
    {
        $url = WebhookUrl::fromString('https://93.184.216.34/inbound');

        $this->assertSame('https://93.184.216.34/inbound', $url->value());
    }

    public function testEqualsComparesByValue(): void
    {
        $a = WebhookUrl::fromString('https://example.com/inbound');
        $b = WebhookUrl::fromString('https://example.com/inbound');
        $c = WebhookUrl::fromString('https://example.com/other');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
