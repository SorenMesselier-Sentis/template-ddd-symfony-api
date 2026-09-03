<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\Shared\Domain\Filter\CursorPage;
use App\Shared\Domain\Filter\CursorPagination;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function delete(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByIdIncludingDeleted(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function existsByEmail(Email $email): bool;

    /** @return array<int, User> */
    public function findByFilters(Filters $filters): array;

    public function countByFilters(Filters $filters): int;

    /** @return CursorPage<User> */
    public function findByFiltersCursor(Filters $filters, CursorPagination $cursorPagination): CursorPage;
}
