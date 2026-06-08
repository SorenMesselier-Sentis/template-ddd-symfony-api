<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Application\Query\GetDocuments;

use App\Document\Application\Query\GetDocuments\GetDocumentsQuery;
use App\Document\Application\Query\GetDocuments\GetDocumentsQueryHandler;
use App\Document\Domain\Entity\Document;
use App\Document\Domain\Repository\DocumentRepositoryInterface;
use App\Document\Domain\Security\OwnerContextInterface;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use App\Tests\Unit\UnitTestCase;

final class GetDocumentsQueryHandlerTest extends UnitTestCase
{
    public function testItReturnsPaginatedDocumentsForAuthenticatedOwner(): void
    {
        $ownerId = OwnerId::random();
        $document = $this->document($ownerId);
        $filters = new Filters([], Order::default(), new Pagination(page: 1, limit: 20));

        $repository = $this->createMock(DocumentRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByOwnerIdAndFilters')
            ->with($ownerId, $filters)
            ->willReturn([$document]);
        $repository->expects($this->once())
            ->method('countByOwnerIdAndFilters')
            ->with($ownerId, $filters)
            ->willReturn(1);

        $ownerContext = $this->createStub(OwnerContextInterface::class);
        $ownerContext->method('ownerId')->willReturn($ownerId);

        $response = (new GetDocumentsQueryHandler($repository, $ownerContext))->__invoke(
            new GetDocumentsQuery(filters: $filters),
        );

        $this->assertCount(1, $response->documents);
        $this->assertSame($document->id()->value(), $response->documents[0]->id);
        $this->assertSame(1, $response->total);
    }

    private function document(OwnerId $ownerId): Document
    {
        $document = Document::create(
            id: DocumentId::random(),
            ownerId: $ownerId,
            bucketName: BucketName::fromString('documents'),
            objectPath: ObjectPath::fromString('owner/doc/file.pdf'),
            originalName: 'file.pdf',
            size: 1024,
            mimeType: MimeType::fromString('application/pdf'),
        );
        $document->pullDomainEvents();

        return $document;
    }
}
