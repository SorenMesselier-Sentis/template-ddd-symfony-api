<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Query\GetApiClientsCollection;

use App\ApiClient\Domain\Entity\ApiClient;
use App\Shared\Domain\Bus\Query\Response;

final class ApiClientItemResponse implements Response
{
    public readonly string $id;
    public readonly string $name;
    public readonly string $status;
    public readonly string $createdAt;

    public function __construct(ApiClient $entity)
    {
        $this->id = $entity->id()->value();
        $this->name = $entity->name();
        $this->status = $entity->status()->value;
        $this->createdAt = $entity->createdAt()->format(\DateTimeInterface::ATOM);
    }
}
