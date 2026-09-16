<?php

declare(strict_types=1);

namespace App\User\Application\Privacy;

use App\Shared\Domain\Privacy\PersonalDataExporterInterface;
use App\User\Domain\Entity\Consent;
use App\User\Domain\Repository\ConsentRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserRole;

final class UserPersonalDataExporter implements PersonalDataExporterInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly ConsentRepositoryInterface $consentRepository,
    ) {
    }

    public function key(): string
    {
        return 'profile';
    }

    public function export(string $subjectId): array
    {
        $userId = UserId::fromString($subjectId);
        $user = $this->repository->findById($userId);

        if (null === $user) {
            return [];
        }

        return [
            'id' => $user->id()->value(),
            'first_name' => $user->firstName()->value(),
            'last_name' => $user->lastName()->value(),
            'email' => $user->email()->value(),
            'status' => $user->status()->value,
            'roles' => array_map(static fn (UserRole $role): string => $role->value, $user->roles()),
            'email_verified' => $user->isEmailVerified(),
            'created_at' => $user->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $user->updatedAt()->format(\DateTimeInterface::ATOM),
            'consents' => array_map(
                static fn (Consent $consent): array => [
                    'type' => $consent->type()->value,
                    'version' => $consent->version(),
                    'given_at' => $consent->givenAt()->format(\DateTimeInterface::ATOM),
                    'withdrawn_at' => $consent->withdrawnAt()?->format(\DateTimeInterface::ATOM),
                ],
                $this->consentRepository->findByUserId($userId),
            ),
        ];
    }
}
