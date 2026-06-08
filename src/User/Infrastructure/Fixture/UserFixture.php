<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Fixture;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Shared\Infrastructure\Fixture\FixtureReference;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (self::definitions() as $definition) {
            $user = User::create(
                id: UserId::fromString($definition['id']),
                firstName: UserName::fromString($definition['firstName']),
                lastName: UserName::fromString($definition['lastName']),
                email: Email::fromString($definition['email']),
                password: HashedPassword::fromPlainPassword(FixtureData::DEFAULT_PASSWORD),
                roles: $definition['roles'],
            );

            $manager->persist($user);
            $this->addReference($definition['reference'], $user);
        }

        $manager->flush();
    }

    /**
     * @return list<array{
     *     reference: string,
     *     id: string,
     *     firstName: string,
     *     lastName: string,
     *     email: string,
     *     roles: list<UserRole>
     * }>
     */
    private static function definitions(): array
    {
        return [
            [
                'reference' => FixtureReference::USER_JOHN,
                'id' => FixtureData::USER_JOHN_ID,
                'firstName' => FixtureData::USER_JOHN_FIRST_NAME,
                'lastName' => FixtureData::USER_JOHN_LAST_NAME,
                'email' => FixtureData::USER_JOHN_EMAIL,
                'roles' => [UserRole::ADMIN, UserRole::USER],
            ],
            [
                'reference' => FixtureReference::USER_JANE,
                'id' => FixtureData::USER_JANE_ID,
                'firstName' => FixtureData::USER_JANE_FIRST_NAME,
                'lastName' => FixtureData::USER_JANE_LAST_NAME,
                'email' => FixtureData::USER_JANE_EMAIL,
                'roles' => [UserRole::USER],
            ],
            [
                'reference' => FixtureReference::USER_BOB,
                'id' => FixtureData::USER_BOB_ID,
                'firstName' => FixtureData::USER_BOB_FIRST_NAME,
                'lastName' => FixtureData::USER_BOB_LAST_NAME,
                'email' => FixtureData::USER_BOB_EMAIL,
                'roles' => [UserRole::USER],
            ],
        ];
    }
}
