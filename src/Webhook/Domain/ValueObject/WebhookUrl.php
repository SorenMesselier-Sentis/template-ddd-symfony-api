<?php

declare(strict_types=1);

namespace App\Webhook\Domain\ValueObject;

use App\Webhook\Domain\Exception\InvalidWebhookUrlException;

/**
 * SSRF guard for admin-supplied webhook URLs: requires HTTPS, and rejects an IP-literal host in
 * any private/loopback/link-local/reserved range (covers the AWS/GCP cloud metadata endpoint,
 * 169.254.169.254, via the link-local range) using PHP's own FILTER_FLAG_NO_PRIV_RANGE |
 * FILTER_FLAG_NO_RES_RANGE — the standard idiom for "only a publicly routable IP passes".
 *
 * Deliberately does NOT resolve DNS for a hostname target: the host could point somewhere
 * internal only after creation (DNS rebinding), which this check can't close without resolving
 * on every delivery attempt too — out of scope for this first pass, see docs/webhooks.md.
 */
final class WebhookUrl
{
    private const BLOCKED_HOSTNAMES = ['localhost'];

    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $parts = parse_url($value);

        if (false === $parts || !isset($parts['scheme'], $parts['host'])) {
            throw InvalidWebhookUrlException::malformed($value);
        }

        if ('https' !== $parts['scheme']) {
            throw InvalidWebhookUrlException::mustBeHttps($value);
        }

        $host = strtolower($parts['host']);

        if (\in_array($host, self::BLOCKED_HOSTNAMES, true) || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            throw InvalidWebhookUrlException::blockedHost($value);
        }

        $ipHost = trim($host, '[]');

        if (false !== filter_var($ipHost, \FILTER_VALIDATE_IP)) {
            $isPublicIp = false !== filter_var($ipHost, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE);

            if (!$isPublicIp) {
                throw InvalidWebhookUrlException::blockedHost($value);
            }
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
