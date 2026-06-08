<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Fixture;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Infrastructure\Fixture\FixtureData;
use App\Shared\Infrastructure\Fixture\FixtureReference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class DocumentFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (self::definitions() as $definition) {
            $documentId = DocumentId::fromString($definition['id']);
            $ownerId = OwnerId::fromString($definition['ownerId']);

            $document = Document::create(
                id: $documentId,
                ownerId: $ownerId,
                bucketName: BucketName::fromString($definition['bucket']),
                objectPath: ObjectPath::forDocument($ownerId, $documentId, $definition['originalName']),
                originalName: $definition['originalName'],
                size: $definition['size'],
                mimeType: MimeType::fromString($definition['mimeType']),
            );

            $document->pullDomainEvents();

            $manager->persist($document);
            $this->addReference($definition['reference'], $document);
        }

        $manager->flush();
    }

    /**
     * @return list<array{
     *     reference: string,
     *     id: string,
     *     ownerId: string,
     *     bucket: string,
     *     originalName: string,
     *     size: int,
     *     mimeType: string
     * }>
     */
    private static function definitions(): array
    {
        return [
            [
                'reference' => FixtureReference::DOCUMENT_JOHN_INVOICE,
                'id' => FixtureData::DOCUMENT_JOHN_INVOICE_ID,
                'ownerId' => FixtureData::USER_JOHN_ID,
                'bucket' => 'invoices',
                'originalName' => 'invoice-2026-01.pdf',
                'size' => 245_760,
                'mimeType' => 'application/pdf',
            ],
            [
                'reference' => FixtureReference::DOCUMENT_JOHN_AVATAR,
                'id' => FixtureData::DOCUMENT_JOHN_AVATAR_ID,
                'ownerId' => FixtureData::USER_JOHN_ID,
                'bucket' => 'documents',
                'originalName' => 'avatar.png',
                'size' => 51_200,
                'mimeType' => 'image/png',
            ],
            [
                'reference' => FixtureReference::DOCUMENT_JANE_CONTRACT,
                'id' => FixtureData::DOCUMENT_JANE_CONTRACT_ID,
                'ownerId' => FixtureData::USER_JANE_ID,
                'bucket' => 'documents',
                'originalName' => 'service-contract.pdf',
                'size' => 512_000,
                'mimeType' => 'application/pdf',
            ],
        ];
    }
}
