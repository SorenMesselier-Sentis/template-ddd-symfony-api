<?php

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
    public const REFERENCE_JHON = "user.jhon";

    public function load(ObjectManager $manager): void
    {
        $user = User::create(
            id:        UserId::random(),
            firstName: UserName::fromString('John'),
            lastName:  UserName::fromString('Doe'),
            email:     Email::fromString('john.doe@example.com'),
            password:  HashedPassword::fromPlainPassword('secret1234'),
        );

        $manager->persist($user);
        $manager->flush();

        $this->addReference(self::REFERENCE_JHON, $user);
    }
}
