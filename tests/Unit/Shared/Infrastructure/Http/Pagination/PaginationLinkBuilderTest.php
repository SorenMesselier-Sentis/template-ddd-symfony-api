<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Pagination;

use App\Shared\Infrastructure\Http\Pagination\PaginationLinkBuilder;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class PaginationLinkBuilderTest extends UnitTestCase
{
    private PaginationLinkBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new PaginationLinkBuilder();
    }

    public function testItStripsApiPrefixFromPath(): void
    {
        $request = Request::create('/api/v1/users', 'GET', ['page' => 1, 'limit' => 10]);

        $links = $this->builder->build($request, page: 1, limit: 10, totalPages: 3);

        $this->assertSame('/v1/users?page=1&limit=10', $links['self']);
        $this->assertSame('/v1/users?page=2&limit=10', $links['next']);
        $this->assertNull($links['previous']);
    }

    public function testItPreservesFiltersAndSort(): void
    {
        $request = Request::create('/api/v1/users', 'GET', [
            'page' => 2,
            'limit' => 5,
            'email' => 'john@example.com',
            'sort' => '-createdAt',
        ]);

        $links = $this->builder->build($request, page: 2, limit: 5, totalPages: 5);

        $this->assertStringContainsString('email=john%40example.com', $links['self']);
        $this->assertStringContainsString('sort=-createdAt', $links['self']);
        $this->assertStringContainsString('page=2', $links['self']);
        $this->assertStringContainsString('limit=5', $links['self']);
    }

    public function testItReturnsNullForNextOnLastPage(): void
    {
        $request = Request::create('/api/v1/users', 'GET', ['page' => 3, 'limit' => 10]);

        $links = $this->builder->build($request, page: 3, limit: 10, totalPages: 3);

        $this->assertNull($links['next']);
        $this->assertSame('/v1/users?page=2&limit=10', $links['previous']);
    }

    public function testItReturnsNullForPreviousOnFirstPage(): void
    {
        $request = Request::create('/api/v1/users', 'GET', ['page' => 1, 'limit' => 10]);

        $links = $this->builder->build($request, page: 1, limit: 10, totalPages: 3);

        $this->assertNull($links['previous']);
        $this->assertSame('/v1/users?page=2&limit=10', $links['next']);
    }
}
