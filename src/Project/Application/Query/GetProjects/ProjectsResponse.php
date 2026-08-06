<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetProjects;

use App\Shared\Domain\Bus\Query\Response;

final class ProjectsResponse implements Response
{
    /**
     * @param list<ProjectItemResponse> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit,
    ) {
    }
}
