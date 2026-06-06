<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Response;

use App\Shared\Infrastructure\Http\Pagination\PaginationLinkBuilder;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiResponseTest extends UnitTestCase
{
    private ApiResponse $apiResponse;

    protected function setUp(): void
    {
        $serializer = $this->createStub(SerializerInterface::class);
        $serializer
            ->method('serialize')
            ->willReturnCallback(static fn (mixed $data): string => json_encode($data, JSON_THROW_ON_ERROR));

        $this->apiResponse = new ApiResponse($serializer, new PaginationLinkBuilder());
    }

    public function testPaginatedResponseIncludesMetaAndLinks(): void
    {
        $request = Request::create('/api/v1/users', 'GET', [
            'page' => 1,
            'limit' => 10,
            'email' => 'john@example.com',
        ]);

        $response = $this->apiResponse->paginated(
            data: [['id' => 'abc']],
            total: 25,
            page: 1,
            limit: 10,
            request: $request,
        );

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([['id' => 'abc']], $payload['data']);
        $this->assertSame([
            'page' => 1,
            'limit' => 10,
            'total_items' => 25,
            'total_pages' => 3,
            'has_next' => true,
            'has_previous' => false,
        ], $payload['meta']);
        $this->assertLink('/v1/users', ['page' => '1', 'limit' => '10', 'email' => 'john@example.com'], $payload['links']['self']);
        $this->assertLink('/v1/users', ['page' => '2', 'limit' => '10', 'email' => 'john@example.com'], $payload['links']['next']);
        $this->assertNull($payload['links']['previous']);
    }

    public function testPaginatedResponseWithZeroTotal(): void
    {
        $request = Request::create('/api/v1/users', 'GET', ['page' => 1, 'limit' => 10]);

        $response = $this->apiResponse->paginated(
            data: [],
            total: 0,
            page: 1,
            limit: 10,
            request: $request,
        );

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([
            'page' => 1,
            'limit' => 10,
            'total_items' => 0,
            'total_pages' => 0,
            'has_next' => false,
            'has_previous' => false,
        ], $payload['meta']);
        $this->assertNull($payload['links']['next']);
        $this->assertNull($payload['links']['previous']);
    }

    /**
     * @param array<string, string> $expectedQuery
     */
    private function assertLink(string $expectedPath, array $expectedQuery, string $link): void
    {
        [$path, $queryString] = array_pad(explode('?', $link, 2), 2, '');
        parse_str($queryString, $query);

        $this->assertSame($expectedPath, $path);
        $this->assertEquals($expectedQuery, $query);
    }
}
