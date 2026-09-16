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
use App\Shared\Infrastructure\Fixture\FixtureFaker;
use App\Shared\Infrastructure\Fixture\FixtureReference;

final class DocumentFixtureCatalog
{
    private const RANDOM_BUCKETS = ['documents', 'invoices'];

    private const RANDOM_MIME_TYPES = [
        'application/pdf' => 'pdf',
        'image/png' => 'png',
        'text/plain' => 'txt',
    ];

    /**
     * @return list<array{
     *     reference: ?string,
     *     id: string,
     *     ownerId: string,
     *     bucket: string,
     *     originalName: string,
     *     size: int,
     *     mimeType: string
     * }>
     */
    public static function definitions(): array
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

    /**
     * Extra unreferenced documents on top of the named ones above, purely to
     * populate the database with a realistic volume — e.g. for exercising
     * `GET /documents` pagination. Owned by the three named users only (never
     * a made-up id), so every random document still belongs to a real,
     * loggable-in account. Count is 0 unless `FIXTURES_RANDOM_DOCUMENT_COUNT`
     * is set (see README "Fixtures and test data"); always 0 in the test env.
     *
     * @return list<array{
     *     reference: ?string,
     *     id: string,
     *     ownerId: string,
     *     bucket: string,
     *     originalName: string,
     *     size: int,
     *     mimeType: string
     * }>
     */
    public static function randomDefinitions(int $count): array
    {
        if ($count < 1) {
            return [];
        }

        $faker = FixtureFaker::create();
        $owners = [FixtureData::USER_JOHN_ID, FixtureData::USER_JANE_ID, FixtureData::USER_BOB_ID];

        $definitions = [];

        for ($i = 0; $i < $count; ++$i) {
            $mimeType = (string) $faker->randomKey(self::RANDOM_MIME_TYPES);
            $extension = self::RANDOM_MIME_TYPES[$mimeType];

            $definitions[] = [
                'reference' => null,
                'id' => DocumentId::random()->value(),
                'ownerId' => FixtureFaker::randomElement($faker, $owners),
                'bucket' => FixtureFaker::randomElement($faker, self::RANDOM_BUCKETS),
                'originalName' => sprintf('%s.%s', $faker->slug(3), $extension),
                'size' => $faker->numberBetween(1_024, 5_000_000),
                'mimeType' => $mimeType,
            ];
        }

        return $definitions;
    }

    /**
     * @param list<array{bucket: string, ...}> $definitions
     *
     * @return list<string>
     */
    public static function bucketNames(array $definitions): array
    {
        $buckets = [];

        foreach ($definitions as $definition) {
            $buckets[$definition['bucket']] = true;
        }

        return array_keys($buckets);
    }

    /**
     * @param array{
     *     reference: ?string,
     *     id: string,
     *     ownerId: string,
     *     bucket: string,
     *     originalName: string,
     *     size: int,
     *     mimeType: string
     * } $definition
     */
    public static function createDocument(array $definition): Document
    {
        $documentId = DocumentId::fromString($definition['id']);
        $ownerId = OwnerId::fromString($definition['ownerId']);

        return Document::create(
            id: $documentId,
            ownerId: $ownerId,
            bucketName: BucketName::fromString($definition['bucket']),
            objectPath: self::objectPathFor($definition),
            originalName: $definition['originalName'],
            size: $definition['size'],
            mimeType: MimeType::fromString($definition['mimeType']),
        );
    }

    /**
     * @param array{
     *     reference: ?string,
     *     id: string,
     *     ownerId: string,
     *     bucket: string,
     *     originalName: string,
     *     size: int,
     *     mimeType: string
     * } $definition
     */
    public static function objectPathFor(array $definition): ObjectPath
    {
        return ObjectPath::forDocument(
            OwnerId::fromString($definition['ownerId']),
            DocumentId::fromString($definition['id']),
            $definition['originalName'],
        );
    }

    /**
     * @param array{
     *     reference: ?string,
     *     id: string,
     *     ownerId: string,
     *     bucket: string,
     *     originalName: string,
     *     size: int,
     *     mimeType: string
     * } $definition
     */
    public static function fixtureContent(array $definition): string
    {
        return match ($definition['mimeType']) {
            'application/pdf' => "%PDF-1.4\n% Fixture: {$definition['originalName']}\n",
            'image/png' => self::fixturePngContent(),
            default => "fixture:{$definition['originalName']}",
        };
    }

    private static function fixturePngContent(): string
    {
        $content = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );

        if (!\is_string($content)) {
            throw new \LogicException('Fixture PNG payload is invalid.');
        }

        return $content;
    }
}
