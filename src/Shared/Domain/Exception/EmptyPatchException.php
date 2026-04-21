<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class EmptyPatchException extends InvalidArgumentException
{
    public function errorCode(): string
    {
        return 'patch_empty';
    }
}
