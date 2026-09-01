<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Persistence\Doctrine\Repository;

use App\ApiClient\Domain\Entity\ApiClient;
use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\ApiClient\Domain\ValueObject\ApiClientStatus;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineFilterApplier;
use App\Shared\Infrastructure\Persistence\Doctrine\Trait\DoctrineRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class DoctrineApiClientRepository implements ApiClientRepositoryInterface
{
    use DoctrineRepositoryTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function save(ApiClient $apiClient): void
    {
        $this->saveEntity($this->em, $apiClient);
    }

    public function findById(ApiClientId $id): ?ApiClient
    {
        $apiClient = $this->em->find(ApiClient::class, $id);

        if (null !== $apiClient && ApiClientStatus::DELETED === $apiClient->status()) {
            return null;
        }

        return $apiClient;
    }

    public function findByIdIncludingDeleted(ApiClientId $id): ?ApiClient
    {
        return $this->em->find(ApiClient::class, $id);
    }

    public function findByFilters(Filters $filters): array
    {
        $qb = $this->activeQueryBuilder();
        DoctrineFilterApplier::apply($qb, $filters, 'e');

        /* @var list<ApiClient> */
        return $qb->getQuery()->getResult();
    }

    public function countByFilters(Filters $filters): int
    {
        $qb = $this->activeQueryBuilder()
            ->select('COUNT(e.id)');

        DoctrineFilterApplier::applyFilters($qb, $filters, 'e');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function activeQueryBuilder(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(ApiClient::class, 'e')
            ->where('e.status != :deleted')
            ->setParameter('deleted', ApiClientStatus::DELETED);
    }
}
