<?php

declare(strict_types=1);

namespace App\Document\Domain\Storage;

use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\PartNumber;

interface MultipartDocumentStorageInterface
{
    public function initiateMultipartUpload(
        BucketName $bucket,
        ObjectPath $objectPath,
        MimeType $mimeType,
    ): string;

    public function uploadPart(
        BucketName $bucket,
        ObjectPath $objectPath,
        string $uploadId,
        PartNumber $partNumber,
        string $content,
    ): string;

    /**
     * @param list<array{partNumber: int, etag: string}> $parts
     */
    public function completeMultipartUpload(
        BucketName $bucket,
        ObjectPath $objectPath,
        string $uploadId,
        array $parts,
    ): void;

    public function abortMultipartUpload(
        BucketName $bucket,
        ObjectPath $objectPath,
        string $uploadId,
    ): void;
}
