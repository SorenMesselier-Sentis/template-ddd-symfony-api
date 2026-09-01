<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Event\UserActivated;
use App\User\Domain\Event\UserCreated;
use App\User\Domain\Event\UserDataErased;
use App\User\Domain\Event\UserDeactivated;
use App\User\Domain\Event\UserDeleted;
use App\User\Domain\Event\UserEmailVerified;
use App\User\Domain\Event\UserRegistered;
use App\User\Domain\Event\UserReplaced;
use App\User\Domain\Event\UserRolesUpdated;
use App\User\Domain\Event\UserUpdated;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserRole;
use App\User\Domain\ValueObject\UserStatus;

final class User
{
    /** @var array<int, DomainEvent> */
    private array $domainEvents = [];

    /**
     * @param list<UserRole> $roles
     */
    private function __construct(
        private readonly UserId $id,
        private UserName $firstName,
        private UserName $lastName,
        private Email $email,
        private HashedPassword $password,
        private UserStatus $status,
        private array $roles,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        private ?\DateTimeImmutable $emailVerifiedAt,
    ) {
    }

    /**
     * @param list<UserRole> $roles
     */
    public static function create(
        UserId $id,
        UserName $firstName,
        UserName $lastName,
        Email $email,
        HashedPassword $password,
        array $roles = [UserRole::USER],
    ): self {
        $now = new \DateTimeImmutable();
        $user = new self(
            id: $id,
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            password: $password,
            status: UserStatus::ACTIVE,
            roles: $roles,
            createdAt: $now,
            updatedAt: $now,
            emailVerifiedAt: $now,
        );

        $user->record(new UserCreated(
            aggregateId: $id->value(),
            firstName: $firstName->value(),
            lastName: $lastName->value(),
            email: $email->value(),
        ));

        return $user;
    }

    public static function register(
        UserId $id,
        UserName $firstName,
        UserName $lastName,
        Email $email,
        HashedPassword $password,
    ): self {
        $now = new \DateTimeImmutable();
        $user = new self(
            id: $id,
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            password: $password,
            status: UserStatus::ACTIVE,
            roles: [UserRole::USER],
            createdAt: $now,
            updatedAt: $now,
            emailVerifiedAt: null,
        );

        $user->record(new UserRegistered(
            aggregateId: $id->value(),
            firstName: $firstName->value(),
            lastName: $lastName->value(),
            email: $email->value(),
        ));

        return $user;
    }

    public function updateName(UserName $firstName, UserName $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->touch();

        $this->record(new UserUpdated($this->id->value()));
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->emailVerifiedAt = null;
        $this->touch();

        $this->record(new UserUpdated($this->id->value()));
    }

    public function updatePassword(HashedPassword $password): void
    {
        $this->password = $password;
        $this->touch();

        $this->record(new UserUpdated($this->id->value()));
    }

    public function verifyEmail(): void
    {
        $this->emailVerifiedAt = new \DateTimeImmutable();
        $this->touch();

        $this->record(new UserEmailVerified($this->id->value()));
    }

    public function activate(): void
    {
        $this->status = UserStatus::ACTIVE;
        $this->touch();

        $this->record(new UserActivated($this->id->value()));
    }

    public function deactivate(): void
    {
        $this->status = UserStatus::INACTIVE;
        $this->touch();

        $this->record(new UserDeactivated($this->id->value()));
    }

    /**
     * @param list<UserRole> $roles
     */
    public function updateRoles(array $roles): void
    {
        $this->roles = $roles;
        $this->updatedAt = new \DateTimeImmutable();

        $this->record(new UserRolesUpdated(
            aggregateId: $this->id->value(),
            roles: array_map(fn (UserRole $role) => $role->value, $roles),
        ));
    }

    public function replace(
        UserName $firstName,
        UserName $lastName,
        Email $email,
        HashedPassword $password,
    ): void {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->password = $password;
        $this->touch();

        $this->record(new UserReplaced($this->id->value()));
    }

    public function delete(): void
    {
        $this->status = UserStatus::DELETED;
        $this->touch();

        $this->record(new UserDeleted(
            aggregateId: $this->id->value(),
            email: $this->email->value(),
        ));
    }

    /**
     * GDPR right to erasure: overwrites every personally identifying field with a redacted
     * placeholder in one shot Does not touch `status`; call delete()
     * as well when the account itself should stop being usable (see UserPersonalDataEraser).
     */
    public function anonymize(
        UserName $firstName,
        UserName $lastName,
        Email $email,
        HashedPassword $password,
    ): void {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->password = $password;
        $this->emailVerifiedAt = null;
        $this->touch();

        $this->record(new UserDataErased($this->id->value()));
    }

    public function isEmailVerified(): bool
    {
        return null !== $this->emailVerifiedAt;
    }

    /**
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function firstName(): UserName
    {
        return $this->firstName;
    }

    public function lastName(): UserName
    {
        return $this->lastName;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    /**
     * @return list<UserRole>
     */
    public function roles(): array
    {
        return $this->roles;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function emailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }
}
