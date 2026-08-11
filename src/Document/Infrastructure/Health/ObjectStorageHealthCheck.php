<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Health;

use App\Document\Infrastructure\Storage\S3ClientFactory;
use App\Shared\Domain\Health\HealthCheckInterface;
use App\Shared\Domain\Health\HealthCheckStatus;
use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ObjectStorageHealthCheck implements HealthCheckInterface
{
    private const TIMEOUT_SECONDS = 3.0;

    /** @var callable(string, string, string, bool, string, bool, ?float): S3Client */
    private $clientFactory;

    public function __construct(
        #[Autowire(env: 'S3_ENDPOINT')]
        private readonly string $endpoint,
        #[Autowire(env: 'S3_ACCESS_KEY')]
        private readonly string $accessKey,
        #[Autowire(env: 'S3_SECRET_KEY')]
        private readonly string $secretKey,
        #[Autowire(env: 'bool:S3_USE_SSL')]
        private readonly bool $useSsl,
        #[Autowire(env: 'S3_REGION')]
        private readonly string $region,
        #[Autowire(env: 'bool:S3_FORCE_PATH_STYLE')]
        private readonly bool $forcePathStyle,
        ?callable $clientFactory = null,
    ) {
        $this->clientFactory = $clientFactory ?? static fn (
            string $endpoint,
            string $accessKey,
            string $secretKey,
            bool $useSsl,
            string $region,
            bool $forcePathStyle,
            ?float $timeoutSeconds,
        ): S3Client => S3ClientFactory::create($endpoint, $accessKey, $secretKey, $useSsl, $region, $forcePathStyle, $timeoutSeconds);
    }

    public function name(): string
    {
        return 'object_storage';
    }

    public function check(): HealthCheckStatus
    {
        try {
            $client = ($this->clientFactory)(
                $this->endpoint,
                $this->accessKey,
                $this->secretKey,
                $this->useSsl,
                $this->region,
                $this->forcePathStyle,
                self::TIMEOUT_SECONDS,
            );

            $client->listBuckets();

            return HealthCheckStatus::ok();
        } catch (\Throwable $exception) {
            return HealthCheckStatus::error($exception->getMessage());
        }
    }
}
