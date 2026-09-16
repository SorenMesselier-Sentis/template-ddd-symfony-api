<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Fixture;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class DocumentFixture extends Fixture
{
    public function __construct(
        private readonly DocumentObjectStorageFixtureSeeder $objectStorageSeeder,
        private readonly int $randomCount = 0,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $definitions = [
            ...DocumentFixtureCatalog::definitions(),
            ...DocumentFixtureCatalog::randomDefinitions($this->randomCount),
        ];

        $this->objectStorageSeeder->seed($definitions);

        foreach ($definitions as $definition) {
            $document = DocumentFixtureCatalog::createDocument($definition);

            $document->pullDomainEvents();

            $manager->persist($document);

            if (null !== $definition['reference']) {
                $this->addReference($definition['reference'], $document);
            }
        }

        $manager->flush();
    }
}
