<?php

declare(strict_types=1);

namespace App\ApiClient\Domain\Repository;

use App\ApiClient\Domain\Entity\ApiClient;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\Shared\Domain\Filter\Filters;

interface ApiClientRepositoryInterface
{
    public function save(ApiClient $apiClient): void;

    /**
     * Excludes soft-deleted clients.
     */
    public function findById(ApiClientId $id): ?ApiClient;

    public function findByIdIncludingDeleted(ApiClientId $id): ?ApiClient;

    /**
     * @return list<ApiClient>
     */
    public function findByFilters(Filters $filters): array;

    public function countByFilters(Filters $filters): int;
}
