<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Fixture;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class DocumentFixture extends Fixture
{
    public function __construct(
        private readonly DocumentObjectStorageFixtureSeeder $objectStorageSeeder,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->objectStorageSeeder->seed();

        foreach (DocumentFixtureCatalog::definitions() as $definition) {
            $document = DocumentFixtureCatalog::createDocument($definition);

            $document->pullDomainEvents();

            $manager->persist($document);
            $this->addReference($definition['reference'], $document);
        }

        $manager->flush();
    }
}
