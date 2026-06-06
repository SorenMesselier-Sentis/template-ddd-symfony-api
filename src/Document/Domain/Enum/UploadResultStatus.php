<?php

declare(strict_types=1);

namespace App\Document\Domain\Enum;

enum UploadResultStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
}
