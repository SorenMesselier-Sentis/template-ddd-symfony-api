<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(UserId $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function existsByEmail(Email $email): bool;
}
