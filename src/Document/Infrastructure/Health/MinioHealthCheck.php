<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Health;

use App\Document\Infrastructure\Storage\MinioS3ClientFactory;
use App\Shared\Domain\Health\HealthCheckInterface;
use App\Shared\Domain\Health\HealthCheckStatus;
use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class MinioHealthCheck implements HealthCheckInterface
{
    private const TIMEOUT_SECONDS = 3.0;

    /** @var callable(string, string, string, bool, ?float): S3Client */
    private $clientFactory;

    public function __construct(
        #[Autowire(env: 'MINIO_ENDPOINT')]
        private readonly string $endpoint,
        #[Autowire(env: 'MINIO_ACCESS_KEY')]
        private readonly string $accessKey,
        #[Autowire(env: 'MINIO_SECRET_KEY')]
        private readonly string $secretKey,
        #[Autowire(env: 'bool:MINIO_USE_SSL')]
        private readonly bool $useSsl,
        ?callable $clientFactory = null,
    ) {
        $this->clientFactory = $clientFactory ?? static fn (
            string $endpoint,
            string $accessKey,
            string $secretKey,
            bool $useSsl,
            ?float $timeoutSeconds,
        ): S3Client => MinioS3ClientFactory::create($endpoint, $accessKey, $secretKey, $useSsl, $timeoutSeconds);
    }

    public function name(): string
    {
        return 'minio';
    }

    public function check(): HealthCheckStatus
    {
        try {
            $client = ($this->clientFactory)(
                $this->endpoint,
                $this->accessKey,
                $this->secretKey,
                $this->useSsl,
                self::TIMEOUT_SECONDS,
            );

            $client->listBuckets();

            return HealthCheckStatus::ok();
        } catch (\Throwable $exception) {
            return HealthCheckStatus::error($exception->getMessage());
        }
    }
}
