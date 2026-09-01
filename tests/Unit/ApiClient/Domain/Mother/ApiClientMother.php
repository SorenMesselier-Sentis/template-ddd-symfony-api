<?php

declare(strict_types=1);

namespace App\Tests\Unit\ApiClient\Domain\Mother;

use App\ApiClient\Domain\Entity\ApiClient;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\ApiClient\Domain\ValueObject\HashedClientSecret;

final class ApiClientMother
{
    /**
     * @param list<string> $scopes
     */
    public static function create(
        ?ApiClientId $id = null,
        string $name = 'Test Worker',
        ?HashedClientSecret $secretHash = null,
        array $scopes = ['documents:write'],
    ): ApiClient {
        return ApiClient::create(
            id: $id ?? ApiClientId::random(),
            name: $name,
            secretHash: $secretHash ?? HashedClientSecret::fromPlainSecret('test-secret'),
            scopes: $scopes,
        );
    }
}
