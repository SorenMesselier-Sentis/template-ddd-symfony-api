<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health\Check;

use App\Shared\Domain\Health\HealthCheckInterface;
use App\Shared\Domain\Health\HealthCheckStatus;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class RabbitMQHealthCheck implements HealthCheckInterface
{
    /** @var callable(array<string, int|string>):object */
    private $connectionFactory;
    private bool $usesDefaultFactory;

    public function __construct(
        #[Autowire(env: 'RABBITMQ_DSN')]
        private readonly string $dsn,
        ?callable $connectionFactory = null,
    ) {
        $this->usesDefaultFactory = null === $connectionFactory;
        $this->connectionFactory = $connectionFactory ?? static fn (array $config): object => new \AMQPConnection($config);
    }

    public function name(): string
    {
        return 'rabbitmq';
    }

    public function check(): HealthCheckStatus
    {
        try {
            if ($this->usesDefaultFactory && !class_exists(\AMQPConnection::class)) {
                return HealthCheckStatus::error('AMQP extension is not installed');
            }

            $connection = ($this->connectionFactory)($this->connectionConfigFromDsn($this->dsn));

            if (!method_exists($connection, 'connect') || !method_exists($connection, 'disconnect')) {
                return HealthCheckStatus::error('Invalid AMQP connection instance');
            }

            $connected = $connection->connect();

            if (true !== $connected) {
                return HealthCheckStatus::error('Unable to connect to RabbitMQ');
            }

            $connection->disconnect();

            return HealthCheckStatus::ok();
        } catch (\Throwable $exception) {
            return HealthCheckStatus::error($exception->getMessage());
        }
    }

    /** @return array<string, int|string> */
    private function connectionConfigFromDsn(string $dsn): array
    {
        $parts = parse_url($dsn);

        if (false === $parts || !isset($parts['host'])) {
            throw new \InvalidArgumentException('Invalid RABBITMQ_DSN format');
        }

        $vhostPath = (string) ($parts['path'] ?? '/');
        $vhost = urldecode(ltrim($vhostPath, '/'));

        if ('' === $vhost) {
            $vhost = '/';
        }

        return [
            'host' => $parts['host'],
            'port' => (int) ($parts['port'] ?? 5672),
            'login' => urldecode((string) ($parts['user'] ?? 'guest')),
            'password' => urldecode((string) ($parts['pass'] ?? 'guest')),
            'vhost' => $vhost,
            'connect_timeout' => 1,
            'read_timeout' => 1,
            'write_timeout' => 1,
        ];
    }
}
