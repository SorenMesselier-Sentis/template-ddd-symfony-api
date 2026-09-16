<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Fixture;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Shared\Infrastructure\Fixture\FixtureFaker;
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
    public function __construct(
        private readonly int $randomCount = 0,
    ) {
    }

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

        $this->loadRandomUsers($manager);

        $manager->flush();
    }

    /**
     * Extra plain (non-referenced) users on top of the named ones above, purely
     * to populate the database with a realistic volume — e.g. for exercising
     * `GET /users` pagination. Count is 0 unless `FIXTURES_RANDOM_USER_COUNT`
     * is set (see README "Fixtures and test data"); always 0 in the test env,
     * so the HTTP suite stays fast and unaffected by random data.
     */
    private function loadRandomUsers(ObjectManager $manager): void
    {
        if ($this->randomCount < 1) {
            return;
        }

        $faker = FixtureFaker::create();

        for ($i = 0; $i < $this->randomCount; ++$i) {
            $user = User::create(
                id: UserId::random(),
                firstName: UserName::fromString(self::randomNamePart($faker->firstName())),
                lastName: UserName::fromString(self::randomNamePart($faker->lastName())),
                email: Email::fromString($faker->unique()->safeEmail()),
                password: HashedPassword::fromPlainPassword(FixtureData::DEFAULT_PASSWORD),
                roles: [UserRole::USER],
            );

            $manager->persist($user);
        }
    }

    /**
     * UserName only accepts lowercase letters, digits and underscores — Faker's
     * locale-aware first/last names can contain spaces, hyphens or accents, so
     * strip anything else and pad short results back above the 3-char minimum.
     */
    private static function randomNamePart(string $raw): string
    {
        $slug = (string) preg_replace('/[^a-z0-9]+/', '', mb_strtolower($raw));

        if (mb_strlen($slug) < 3) {
            $slug .= 'usr';
        }

        return mb_substr($slug, 0, 30);
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
