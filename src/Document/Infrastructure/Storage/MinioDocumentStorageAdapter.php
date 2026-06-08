<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Storage;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\Storage\DocumentPresignedUrlGeneratorInterface;
use App\Document\Domain\Storage\DocumentStorageInterface;
use App\Document\Domain\Storage\MultipartDocumentStorageInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\PartNumber;
use App\Document\Domain\ValueObject\PresignedUrl;
use Aws\S3\S3Client;

final class MinioDocumentStorageAdapter implements DocumentStorageInterface, MultipartDocumentStorageInterface, DocumentPresignedUrlGeneratorInterface
{
    private readonly S3Client $client;

    public function __construct(
        string $endpoint,
        string $accessKey,
        string $secretKey,
        bool $useSsl,
    ) {
        $this->client = MinioS3ClientFactory::create($endpoint, $accessKey, $secretKey, $useSsl);
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

    public function delete(BucketName $bucket, ObjectPath $objectPath): void
    {
        $this->client->deleteObject([
            'Bucket' => $bucket->value(),
            'Key' => $objectPath->value(),
        ]);
    }

    public function initiateMultipartUpload(
        BucketName $bucket,
        ObjectPath $objectPath,
        MimeType $mimeType,
    ): string {
        $result = AwsS3ResultHelper::toArray($this->client->createMultipartUpload([
            'Bucket' => $bucket->value(),
            'Key' => $objectPath->value(),
            'ContentType' => $mimeType->value(),
        ]));

        return AwsS3ResultHelper::requireString($result, 'UploadId');
    }

    public function uploadPart(
        BucketName $bucket,
        ObjectPath $objectPath,
        string $uploadId,
        PartNumber $partNumber,
        string $content,
    ): string {
        $result = AwsS3ResultHelper::toArray($this->client->uploadPart([
            'Bucket' => $bucket->value(),
            'Key' => $objectPath->value(),
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber->value(),
            'Body' => $content,
        ]));

        return AwsS3ResultHelper::requireString($result, 'ETag');
    }

    public function completeMultipartUpload(
        BucketName $bucket,
        ObjectPath $objectPath,
        string $uploadId,
        array $parts,
    ): void {
        $this->client->completeMultipartUpload([
            'Bucket' => $bucket->value(),
            'Key' => $objectPath->value(),
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => array_map(
                    static fn (array $part): array => [
                        'PartNumber' => $part['partNumber'],
                        'ETag' => $part['etag'],
                    ],
                    $parts,
                ),
            ],
        ]);
    }

    public function abortMultipartUpload(
        BucketName $bucket,
        ObjectPath $objectPath,
        string $uploadId,
    ): void {
        $this->client->abortMultipartUpload([
            'Bucket' => $bucket->value(),
            'Key' => $objectPath->value(),
            'UploadId' => $uploadId,
        ]);
    }

    public function generatePresignedDownloadUrl(Document $document, int $ttlSeconds): PresignedUrl
    {
        $command = $this->client->getCommand('GetObject', [
            'Bucket' => $document->bucketName()->value(),
            'Key' => $document->objectPath()->value(),
        ]);

        $expiresAt = (new \DateTimeImmutable())->modify(sprintf('+%d seconds', $ttlSeconds));
        $request = $this->client->createPresignedRequest($command, $expiresAt);

        return new PresignedUrl(
            url: (string) $request->getUri(),
            expiresInSeconds: $ttlSeconds,
            expiresAt: $expiresAt,
        );
    }
}
