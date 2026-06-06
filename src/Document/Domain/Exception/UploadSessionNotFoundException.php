<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class UploadSessionNotFoundException extends NotFoundException
{
    public static function withUploadId(string $uploadId): self
    {
        return new self(sprintf('Upload session "%s" was not found or has expired.', $uploadId));
    }

    public function errorCode(): string
    {
        return 'document.upload_session_not_found';
    }
}
