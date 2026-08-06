<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetTasks;

use App\Shared\Domain\Bus\Query\Response;

final class TasksResponse implements Response
{
    /**
     * @param list<TaskItemResponse> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit,
    ) {
    }
}
