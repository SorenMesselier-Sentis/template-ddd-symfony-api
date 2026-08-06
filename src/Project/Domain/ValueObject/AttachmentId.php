<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

/**
 * References an attached Document by stable UUID — cross-BC, no Doctrine
 * relation to the Document BC (see docs/ddd-conventions.md). Never validated
 * against the Document repository: Project's Infrastructure must not import
 * anything from the Document BC.
 */
final class AttachmentId extends Uuid
{
}
