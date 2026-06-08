<?php

declare(strict_types=1);

namespace App\Tests\Integration\Document\Infrastructure;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Document\Infrastructure\Persistence\Doctrine\Repository\DoctrineDocumentRepository;
use App\Shared\Domain\Filter\Filter;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use App\Tests\Integration\IntegrationTestCase;

final class DoctrineDocumentRepositoryTest extends IntegrationTestCase
{
    private DoctrineDocumentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DoctrineDocumentRepository($this->em);
    }

    public function testFindByIdReturnsNullForDeletedDocument(): void
    {
        $ownerId = OwnerId::random();
        $document = $this->createDocument($ownerId, 'documents', 'application/pdf');
        $document->delete(false);
        $document->pullDomainEvents();

        $this->repository->save($document);

        $this->assertNull($this->repository->findById($document->id()));
        $this->assertNotNull($this->repository->findByIdIncludingDeleted($document->id()));
    }

    public function testFindByOwnerIdReturnsOnlyActiveDocumentsOrderedByCreatedAtDesc(): void
    {
        $ownerId = OwnerId::random();
        $otherOwnerId = OwnerId::random();

        $older = $this->createDocument($ownerId, 'documents', 'application/pdf');
        $this->repository->save($older);
        sleep(1);

        $newer = $this->createDocument($ownerId, 'archive', 'application/pdf');
        $deleted = $this->createDocument($ownerId, 'documents', 'application/pdf');
        $deleted->delete(false);
        $deleted->pullDomainEvents();
        $foreign = $this->createDocument($otherOwnerId, 'documents', 'application/pdf');

        foreach ([$newer, $deleted, $foreign] as $document) {
            $this->repository->save($document);
        }

        $documents = $this->repository->findByOwnerId($ownerId);

        $this->assertCount(2, $documents);
        $this->assertTrue($newer->id()->equals($documents[0]->id()));
        $this->assertTrue($older->id()->equals($documents[1]->id()));
        $this->assertTrue($ownerId->equals($documents[0]->ownerId()));
    }

    public function testFindByOwnerIdAndFiltersSupportsBucketMimeTypeAndCreatedAtRange(): void
    {
        $ownerId = OwnerId::random();
        $matching = $this->createDocument($ownerId, 'documents', 'application/pdf');
        $otherBucket = $this->createDocument($ownerId, 'archive', 'application/pdf');
        $otherMime = $this->createDocument($ownerId, 'documents', 'image/png');

        foreach ([$matching, $otherBucket, $otherMime] as $document) {
            $this->repository->save($document);
        }

        $bucketFilters = new Filters(
            [Filter::equal('bucketName', 'documents')],
            Order::default(),
            Pagination::fromRequest(1, 20),
        );
        $this->assertCount(2, $this->repository->findByOwnerIdAndFilters($ownerId, $bucketFilters));
        $this->assertSame(2, $this->repository->countByOwnerIdAndFilters($ownerId, $bucketFilters));

        $mimeFilters = new Filters(
            [Filter::equal('mimeType', 'application/pdf')],
            Order::default(),
            Pagination::fromRequest(1, 20),
        );
        $this->assertCount(2, $this->repository->findByOwnerIdAndFilters($ownerId, $mimeFilters));

        $createdAt = $matching->createdAt()->format('Y-m-d');
        $dateFilters = new Filters(
            [
                Filter::min('createdAt', $createdAt),
                Filter::max('createdAt', $createdAt.' 23:59:59'),
            ],
            Order::default(),
            Pagination::fromRequest(1, 20),
        );
        $this->assertGreaterThanOrEqual(1, count($this->repository->findByOwnerIdAndFilters($ownerId, $dateFilters)));
    }

    private function createDocument(OwnerId $ownerId, string $bucket, string $mimeType): Document
    {
        $document = Document::create(
            id: DocumentId::random(),
            ownerId: $ownerId,
            bucketName: BucketName::fromString($bucket),
            objectPath: ObjectPath::forDocument($ownerId, DocumentId::random(), 'file.bin'),
            originalName: 'file.bin',
            size: 512,
            mimeType: MimeType::fromString($mimeType),
        );
        $document->pullDomainEvents();

        return $document;
    }
}
