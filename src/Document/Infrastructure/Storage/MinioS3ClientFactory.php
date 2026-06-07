<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Storage;

use Aws\S3\S3Client;

final class MinioS3ClientFactory
{
    public static function create(
        string $endpoint,
        string $accessKey,
        string $secretKey,
        bool $useSsl,
        ?float $timeoutSeconds = null,
    ): S3Client {
        $http = [
            'verify' => $useSsl,
        ];

        if (null !== $timeoutSeconds) {
            $http['connect_timeout'] = $timeoutSeconds;
            $http['timeout'] = $timeoutSeconds;
        }

        return new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
            'http' => $http,
        ]);
    }
}
