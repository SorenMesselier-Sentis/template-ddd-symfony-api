<?php

declare(strict_types=1);

namespace App\Tests\Unit\Document\Infrastructure\Http;

use App\Document\Domain\Exception\BucketNotFoundException;
use App\Document\Domain\Exception\FileTooLargeException;
use App\Document\Domain\Exception\InvalidMimeTypeException;
use App\Document\Domain\Exception\InvalidMultipartFileSizeException;
use App\Document\Domain\Exception\InvalidPartSizeException;
use App\Document\Domain\Exception\UploadSessionNotFoundException;
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

    public function testItMapsUploadSessionNotFoundTo404(): void
    {
        $exception = UploadSessionNotFoundException::withUploadId('abc');

        $this->assertTrue($this->mapper->supports($exception));
        $this->assertSame([404, 'document.upload_session_not_found'], $this->mapper->resolve($exception));
    }

    public function testItMapsInvalidPartSizeTo422(): void
    {
        $exception = InvalidPartSizeException::empty();

        $this->assertTrue($this->mapper->supports($exception));
        $this->assertSame([422, 'document.invalid_part_size'], $this->mapper->resolve($exception));
    }

    public function testItMapsInvalidMultipartFileSizeTo422(): void
    {
        $exception = InvalidMultipartFileSizeException::outOfRange(104857600, 5368709120);

        $this->assertTrue($this->mapper->supports($exception));
        $this->assertSame([422, 'document.invalid_multipart_file_size'], $this->mapper->resolve($exception));
    }
}
