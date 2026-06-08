<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Filter;

use App\Shared\Domain\Filter\Filter;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use Symfony\Component\HttpFoundation\Request;

final class FiltersBuilder
{
    /**
     * Allowed fields to be filtered they need to be defined by the context (the controller).
     *
     * @param array<string,mixed> $allowedFilters
     */
    public static function fromRequest(Request $request, array $allowedFilters): Filters
    {
        $filters = [];
        $queryParams = $request->query->all();

        foreach ($allowedFilters as $field => $type) {
            match ($type) {
                'equal' => self::buildEqual($queryParams, $field, $filters),
                'range' => self::buildRange($queryParams, $field, $filters),
                'in' => self::buildIn($queryParams, $field, $filters),
                default => null,
            };
        }

        $order = self::buildOrder($queryParams);
        $pagination = self::buildPagination($queryParams);

        return new Filters($filters, $order, $pagination);
    }

    /**
     * @param array<string,mixed> $params
     * @param array<Filter>       $filters
     */
    private static function buildEqual(array $params, string $field, array &$filters): void
    {
        if (isset($params[$field]) && '' !== $params[$field]) {
            $filters[] = Filter::equal($field, $params[$field]);
        }
    }

    /**
     * @param array<string,mixed> $params
     * @param array<Filter>       $filters
     */
    private static function buildRange(array $params, string $field, array &$filters): void
    {
        if (!isset($params[$field]) || !is_array($params[$field])) {
            return;
        }

        if (isset($params[$field]['min']) && '' !== $params[$field]['min']) {
            $filters[] = Filter::min($field, $params[$field]['min']);
        }

        if (isset($params[$field]['max']) && '' !== $params[$field]['max']) {
            $filters[] = Filter::max($field, $params[$field]['max']);
        }
    }

    /**
     * @param array<string,mixed> $params
     * @param array<Filter>       $filters
     */
    private static function buildIn(array $params, string $field, array &$filters): void
    {
        if (isset($params[$field]) && is_array($params[$field])) {
            $filters[] = Filter::in($field, $params[$field]);
        }

        if (isset($params[$field]) && is_string($params[$field])) {
            $filters[] = Filter::in($field, explode(',', $params[$field]));
        }
    }

    /**
     * @param array<string,mixed> $params
     */
    private static function buildOrder(array $params): Order
    {
        $sort = $params['sort'] ?? null;

        if (\is_string($sort) && '' !== $sort) {
            return Order::fromString($sort);
        }

        return Order::default();
    }

    /**
     * @param array<string,mixed> $params
     */
    private static function buildPagination(array $params): Pagination
    {
        return Pagination::fromRequest(
            page: self::toPositiveInt($params['page'] ?? null, 1),
            limit: self::toPositiveInt($params['limit'] ?? null, 20),
        );
    }

    private static function toPositiveInt(mixed $value, int $default): int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value) && '' !== $value && ctype_digit($value)) {
            return (int) $value;
        }

        return $default;
    }
}
