<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

/**
 * References the owning User by stable UUID — cross-BC, no Doctrine relation
 * to the User BC (see docs/ddd-conventions.md).
 */
final class OwnerId extends Uuid
{
}
