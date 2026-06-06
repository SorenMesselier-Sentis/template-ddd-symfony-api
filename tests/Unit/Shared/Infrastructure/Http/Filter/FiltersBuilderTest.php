<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Filter;

use App\Shared\Domain\Filter\FilterOperator;
use App\Shared\Infrastructure\Http\Filter\FiltersBuilder;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class FiltersBuilderTest extends UnitTestCase
{
    public function testItBuildsEqualFilters(): void
    {
        $request = Request::create('/users', 'GET', [
            'email' => 'john@example.com',
            'firstName' => 'John',
        ]);

        $filters = FiltersBuilder::fromRequest($request, [
            'email' => 'equal',
            'firstName' => 'equal',
            'lastName' => 'equal',
        ]);

        $this->assertCount(2, $filters->all());
        $this->assertSame('email', $filters->all()[0]->field);
        $this->assertSame('john@example.com', $filters->all()[0]->value);
        $this->assertSame(FilterOperator::EQUAL, $filters->all()[0]->operator);
    }

    public function testItReadsLimitParameter(): void
    {
        $request = Request::create('/users', 'GET', [
            'page' => 2,
            'limit' => 5,
        ]);

        $filters = FiltersBuilder::fromRequest($request, []);

        $this->assertSame(2, $filters->pagination->page);
        $this->assertSame(5, $filters->pagination->limit);
        $this->assertSame(5, $filters->pagination->offset);
    }

    public function testItBuildsRangeFilters(): void
    {
        $request = Request::create('/items', 'GET', [
            'price' => ['min' => 10, 'max' => 100],
        ]);

        $filters = FiltersBuilder::fromRequest($request, ['price' => 'range']);

        $this->assertCount(2, $filters->all());
        $this->assertSame(FilterOperator::GREATER_THAN_OR_EQUAL, $filters->all()[0]->operator);
        $this->assertSame(FilterOperator::LESS_THAN_OR_EQUAL, $filters->all()[1]->operator);
    }

    public function testItBuildsInFilterFromCommaSeparatedString(): void
    {
        $request = Request::create('/items', 'GET', [
            'status' => 'active,pending',
        ]);

        $filters = FiltersBuilder::fromRequest($request, ['status' => 'in']);

        $this->assertCount(1, $filters->all());
        $this->assertSame(FilterOperator::IN, $filters->all()[0]->operator);
        $this->assertSame(['active', 'pending'], $filters->all()[0]->value);
    }
}
