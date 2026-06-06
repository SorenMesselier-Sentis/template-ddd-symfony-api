<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Infrastructure\Http;

use App\Document\Domain\Exception\BucketNotFoundException;
use App\Document\Domain\Exception\FileTooLargeException;
use App\Document\Domain\Exception\InvalidMimeTypeException;
use App\Document\Infrastructure\Http\DocumentExceptionMapper;
use App\Tests\Unit\UnitTestCase;

final class DocumentExceptionMapperTest extends UnitTestCase
{
    private DocumentExceptionMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new DocumentExceptionMapper();
    }

    public function testItMapsBucketNotFoundTo404(): void
    {
        $exception = BucketNotFoundException::withName('missing');

        $this->assertTrue($this->mapper->supports($exception));
        $this->assertSame([404, 'document.bucket_not_found'], $this->mapper->resolve($exception));
    }

    public function testItMapsInvalidMimeTypeTo422(): void
    {
        $exception = InvalidMimeTypeException::notAllowed('text/plain', 'documents');

        $this->assertTrue($this->mapper->supports($exception));
        $this->assertSame([422, 'document.invalid_mime_type'], $this->mapper->resolve($exception));
    }

    public function testItMapsFileTooLargeTo422(): void
    {
        $exception = FileTooLargeException::exceedsMaximum(104857600);

        $this->assertTrue($this->mapper->supports($exception));
        $this->assertSame([422, 'document.file_too_large'], $this->mapper->resolve($exception));
    }
}
