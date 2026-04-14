<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixture;

use App\User\Infrastructure\Fixture\UserFixture;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void {}

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
