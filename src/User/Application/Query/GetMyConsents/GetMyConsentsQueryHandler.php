<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetMyConsents;

use App\User\Domain\Repository\ConsentRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetMyConsentsQueryHandler
{
    public function __construct(
        private readonly ConsentRepositoryInterface $repository,
        private readonly UserContextInterface $userContext,
    ) {
    }

    public function __invoke(GetMyConsentsQuery $query): ConsentsResponse
    {
        $consents = $this->repository->findByUserId($this->userContext->userId());

        return new ConsentsResponse(
            consents: array_values(array_map(static fn ($consent) => new ConsentResponse($consent), $consents)),
        );
    }
}
