<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Fixture;

use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixture extends Fixture
{
    public const REFERENCE_JOHN = 'user.john';

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john.doe@example.com',
                'password' => 'secret1234',
                'ref' => self::REFERENCE_JOHN,
            ],
            [
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'email' => 'jane.doe@example.com',
                'password' => 'secret1234',
                'ref' => 'user.jane',
            ],
            [
                'firstName' => 'Bob',
                'lastName' => 'Smith',
                'email' => 'bob.smith@example.com',
                'password' => 'secret1234',
                'ref' => 'user.bob',
            ],
        ];

        foreach ($users as $data) {
            $user = User::create(
                id: UserId::random(),
                firstName: UserName::fromString($data['firstName']),
                lastName: UserName::fromString($data['lastName']),
                email: Email::fromString($data['email']),
                password: HashedPassword::fromPlainPassword($data['password']),
            );

            $manager->persist($user);
            $this->addReference($data['ref'], $user);
        }

        $manager->flush();
    }
}
