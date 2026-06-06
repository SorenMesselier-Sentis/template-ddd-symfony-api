<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\Filter\Filter;
use App\Shared\Domain\Filter\FilterOperator;
use App\Shared\Domain\Filter\Filters;
use Doctrine\ORM\QueryBuilder;

final class DoctrineFilterApplier
{
    public static function apply(QueryBuilder $qb, Filters $filters, string $alias): QueryBuilder
    {
        self::applyFilters($qb, $filters, $alias);
        self::applyOrderAndPagination($qb, $filters, $alias);

        return $qb;
    }

    public static function applyFilters(QueryBuilder $qb, Filters $filters, string $alias): QueryBuilder
    {
        foreach ($filters->all() as $i => $filter) {
            self::applyFilter($qb, $filter, $alias, $i);
        }

        return $qb;
    }

    public static function applyOrderAndPagination(QueryBuilder $qb, Filters $filters, string $alias): QueryBuilder
    {
        $qb->orderBy(
            sprintf('%s.%s', $alias, $filters->order->field),
            $filters->order->type->value,
        );

        $qb->setFirstResult($filters->pagination->offset)
           ->setMaxResults($filters->pagination->limit);

        return $qb;
    }

    private static function applyFilter(
        QueryBuilder $qb,
        Filter $filter,
        string $alias,
        int $index,
    ): void {
        $param = sprintf('filter_%s_%d', $filter->field, $index);
        $field = sprintf('%s.%s', $alias, $filter->field);

        match ($filter->operator) {
            FilterOperator::EQUAL => $qb
                ->andWhere(sprintf('%s = :%s', $field, $param))
                ->setParameter($param, $filter->value),

            FilterOperator::GREATER_THAN_OR_EQUAL => $qb
                ->andWhere(sprintf('%s >= :%s', $field, $param))
                ->setParameter($param, $filter->value),

            FilterOperator::LESS_THAN_OR_EQUAL => $qb
                ->andWhere(sprintf('%s <= :%s', $field, $param))
                ->setParameter($param, $filter->value),

            FilterOperator::IN => $qb
                ->andWhere(sprintf('%s IN (:%s)', $field, $param))
                ->setParameter($param, $filter->value),
        };
    }
}
