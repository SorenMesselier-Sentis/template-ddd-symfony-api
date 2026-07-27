<?php

declare(strict_types=1);

namespace App\Shared\Domain\Filter;

final class Filters
{
    /** @var Filter[] */
    private array $filters;

    /**
     * @param Filter[] $filters
     */
    public function __construct(
        array $filters,
        public readonly Order $order,
        public readonly Pagination $pagination,
    ) {
        $this->filters = $filters;
    }

    /** @return Filter[] */
    public function all(): array
    {
        return $this->filters;
    }
}
