<?php

declare(strict_types=1);

namespace App\Document\Domain\Enum;

enum MultipartUploadStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case ABORTED = 'aborted';
}
