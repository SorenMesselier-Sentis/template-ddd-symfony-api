<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Fixture;

use App\Shared\Infrastructure\Fixture\FixtureReference;
use App\User\Domain\Entity\Consent;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\ConsentId;
use App\User\Domain\ValueObject\ConsentType;
use App\User\Infrastructure\Legal\LegalDocumentVersion;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ConsentFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (self::definitions() as $definition) {
            /** @var User $user */
            $user = $this->getReference($definition['userReference'], User::class);

            $consent = Consent::give(
                id: ConsentId::random(),
                userId: $user->id(),
                type: $definition['type'],
                version: $definition['version'],
            );

            $manager->persist($consent);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixture::class];
    }

    /**
     * @return list<array{userReference: string, type: ConsentType, version: string}>
     */
    private static function definitions(): array
    {
        return [
            ['userReference' => FixtureReference::USER_JOHN, 'type' => ConsentType::TERMS_OF_SERVICE, 'version' => LegalDocumentVersion::TERMS_OF_SERVICE],
            ['userReference' => FixtureReference::USER_JOHN, 'type' => ConsentType::PRIVACY_POLICY, 'version' => LegalDocumentVersion::PRIVACY_POLICY],
            ['userReference' => FixtureReference::USER_JANE, 'type' => ConsentType::TERMS_OF_SERVICE, 'version' => LegalDocumentVersion::TERMS_OF_SERVICE],
            ['userReference' => FixtureReference::USER_JANE, 'type' => ConsentType::PRIVACY_POLICY, 'version' => LegalDocumentVersion::PRIVACY_POLICY],
        ];
    }
}
