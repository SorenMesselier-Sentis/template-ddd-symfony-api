<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class EmptyPatchException extends InvalidArgumentException
{
    public function errorCode(): string
    {
        return 'patch_empty';
    }
}
