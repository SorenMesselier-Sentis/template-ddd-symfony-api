<?php

declare(strict_types=1);

namespace App\Document\Domain\Storage;

use App\Document\Domain\ValueObject\BucketInfo;
use App\Document\Domain\ValueObject\BucketName;

interface BucketManagerInterface
{
    public function create(BucketName $bucket): void;

    public function delete(BucketName $bucket): void;

    /**
     * @return list<BucketInfo>
     */
    public function list(): array;
}
