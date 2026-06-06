<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Storage;

use App\Document\Domain\Storage\DocumentStorageInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use Aws\S3\S3Client;

final class MinioDocumentStorageAdapter implements DocumentStorageInterface
{
    private readonly S3Client $client;

    public function __construct(
        string $endpoint,
        string $accessKey,
        string $secretKey,
        bool $useSsl,
    ) {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
            'http' => [
                'verify' => $useSsl,
            ],
        ]);
    }

    public function upload(
        BucketName $bucket,
        ObjectPath $objectPath,
        string $content,
        MimeType $mimeType,
    ): void {
        $this->client->putObject([
            'Bucket' => $bucket->value(),
            'Key' => $objectPath->value(),
            'Body' => $content,
            'ContentType' => $mimeType->value(),
        ]);
    }
}
