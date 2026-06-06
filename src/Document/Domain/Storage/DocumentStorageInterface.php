<?php

declare(strict_types=1);

namespace App\Document\Domain\Storage;

use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;

interface DocumentStorageInterface
{
    public function upload(
        BucketName $bucket,
        ObjectPath $objectPath,
        string $content,
        MimeType $mimeType,
    ): void;
}
