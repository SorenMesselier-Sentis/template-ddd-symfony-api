<?php

declare(strict_types=1);

namespace App\Document\Domain\Storage;

use App\Document\Domain\ValueObject\BucketName;

interface BucketExistenceCheckerInterface
{
    public function exists(BucketName $bucket): bool;
}
