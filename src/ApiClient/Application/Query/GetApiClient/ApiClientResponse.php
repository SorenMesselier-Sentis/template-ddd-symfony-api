<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Query\GetApiClient;

use App\ApiClient\Domain\Entity\ApiClient;
use App\Shared\Domain\Bus\Query\Response;

final class ApiClientResponse implements Response
{
    public readonly string $id;
    public readonly string $name;
    /** @var list<string> */
    public readonly array $scopes;
    public readonly string $status;
    public readonly string $createdAt;
    public readonly ?string $lastUsedAt;

    public function __construct(ApiClient $entity)
    {
        $this->id = $entity->id()->value();
        $this->name = $entity->name();
        $this->scopes = $entity->scopes();
        $this->status = $entity->status()->value;
        $this->createdAt = $entity->createdAt()->format(\DateTimeInterface::ATOM);
        $this->lastUsedAt = $entity->lastUsedAt()?->format(\DateTimeInterface::ATOM);
    }
}
