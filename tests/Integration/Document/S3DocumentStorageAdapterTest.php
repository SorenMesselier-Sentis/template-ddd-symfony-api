<?php

declare(strict_types=1);

namespace App\Tests\Integration\Document;

use App\Document\Domain\Entity\Document;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\DocumentId;
use App\Document\Domain\ValueObject\MimeType;
use App\Document\Domain\ValueObject\ObjectPath;
use App\Document\Domain\ValueObject\OwnerId;
use App\Document\Domain\ValueObject\PartNumber;
use App\Document\Infrastructure\Storage\S3BucketExistenceChecker;
use App\Document\Infrastructure\Storage\S3BucketManager;
use App\Document\Infrastructure\Storage\S3DocumentStorageAdapter;
use App\Tests\Support\ObjectStorageTestTrait;
use Aws\S3\Exception\S3Exception;
use PHPUnit\Framework\TestCase;

final class S3DocumentStorageAdapterTest extends TestCase
{
    use ObjectStorageTestTrait;
    private const BUCKET = 'integration-documents';

    private ?S3DocumentStorageAdapter $adapter = null;

    private ?S3BucketManager $bucketManager = null;

    private ?S3BucketExistenceChecker $bucketChecker = null;

    protected function setUp(): void
    {
        if (!$this->isObjectStorageAvailable()) {
            $this->markTestSkipped('RustFS is not available.');
        }

        $this->ensureBucket(self::BUCKET);
        $this->adapter = $this->createAdapter();
        $this->bucketManager = $this->createBucketManager();
        $this->bucketChecker = $this->createBucketChecker();
    }

    public function testItUploadsObjectToObjectStorage(): void
    {
        $bucket = BucketName::fromString(self::BUCKET);
        $path = ObjectPath::fromString('integration/upload.txt');

        $this->adapter->upload($bucket, $path, 'hello', MimeType::fromString('text/plain'));

        $object = $this->createObjectStorageS3Client()->getObject([
            'Bucket' => $bucket->value(),
            'Key' => $path->value(),
        ]);

        $this->assertSame('hello', (string) $object['Body']);
    }

    public function testItDeletesObjectFromObjectStorage(): void
    {
        $bucket = BucketName::fromString(self::BUCKET);
        $path = ObjectPath::fromString('integration/delete.txt');

        $this->adapter->upload($bucket, $path, 'delete-me', MimeType::fromString('text/plain'));
        $this->adapter->delete($bucket, $path);

        $client = $this->createObjectStorageS3Client();

        try {
            $client->getObject([
                'Bucket' => $bucket->value(),
                'Key' => $path->value(),
            ]);
            $this->fail('Expected deleted object to be missing.');
        } catch (S3Exception $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function testItCompletesMultipartUpload(): void
    {
        $bucket = BucketName::fromString(self::BUCKET);
        $path = ObjectPath::fromString('integration/multipart.bin');
        $mimeType = MimeType::fromString('application/octet-stream');

        $uploadId = $this->adapter->initiateMultipartUpload($bucket, $path, $mimeType);
        $etag = $this->adapter->uploadPart(
            $bucket,
            $path,
            $uploadId,
            PartNumber::fromInt(1),
            str_repeat('a', 5 * 1024 * 1024),
        );

        $this->adapter->completeMultipartUpload($bucket, $path, $uploadId, [
            ['partNumber' => 1, 'etag' => $etag],
        ]);

        $object = $this->createObjectStorageS3Client()->getObject([
            'Bucket' => $bucket->value(),
            'Key' => $path->value(),
        ]);

        $this->assertSame(5 * 1024 * 1024, strlen((string) $object['Body']));
    }

    public function testItAbortsMultipartUpload(): void
    {
        $bucket = BucketName::fromString(self::BUCKET);
        $path = ObjectPath::fromString('integration/aborted.bin');
        $mimeType = MimeType::fromString('application/octet-stream');

        $uploadId = $this->adapter->initiateMultipartUpload($bucket, $path, $mimeType);
        $this->adapter->abortMultipartUpload($bucket, $path, $uploadId);

        $client = $this->createObjectStorageS3Client();

        try {
            $client->getObject([
                'Bucket' => $bucket->value(),
                'Key' => $path->value(),
            ]);
            $this->fail('Expected aborted multipart object to be missing.');
        } catch (S3Exception $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function testItGeneratesPresignedDownloadUrl(): void
    {
        $bucket = BucketName::fromString(self::BUCKET);
        $path = ObjectPath::fromString('integration/presigned.pdf');
        $ownerId = OwnerId::random();
        $documentId = DocumentId::random();

        $this->adapter->upload($bucket, $path, '%PDF-1.4', MimeType::fromString('application/pdf'));

        $document = Document::create(
            id: $documentId,
            ownerId: $ownerId,
            bucketName: $bucket,
            objectPath: $path,
            originalName: 'presigned.pdf',
            size: 8,
            mimeType: MimeType::fromString('application/pdf'),
        );

        $presignedUrl = $this->adapter->generatePresignedDownloadUrl($document, 3600);

        $this->assertStringContainsString($bucket->value(), $presignedUrl->url());
        $this->assertStringContainsString($path->value(), $presignedUrl->url());
        $this->assertSame(3600, $presignedUrl->expiresInSeconds());
    }

    public function testBucketLifecycleOperations(): void
    {
        $bucketName = 'integration-'.bin2hex(random_bytes(4));
        $bucket = BucketName::fromString($bucketName);

        $this->assertFalse($this->bucketChecker->exists($bucket));

        $this->bucketManager->create($bucket);

        $this->assertTrue($this->bucketChecker->exists($bucket));

        $listedBuckets = array_map(
            static fn ($info) => $info->name->value(),
            $this->bucketManager->list(),
        );
        $this->assertContains($bucketName, $listedBuckets);

        $this->bucketManager->delete($bucket);

        $this->assertFalse($this->bucketChecker->exists($bucket));
    }

    private function createAdapter(): S3DocumentStorageAdapter
    {
        return new S3DocumentStorageAdapter(
            endpoint: $this->s3Endpoint(),
            accessKey: $this->s3AccessKey(),
            secretKey: $this->s3SecretKey(),
            useSsl: false,
        );
    }

    private function createBucketManager(): S3BucketManager
    {
        return new S3BucketManager(
            endpoint: $this->s3Endpoint(),
            accessKey: $this->s3AccessKey(),
            secretKey: $this->s3SecretKey(),
            useSsl: false,
        );
    }

    private function createBucketChecker(): S3BucketExistenceChecker
    {
        return new S3BucketExistenceChecker(
            endpoint: $this->s3Endpoint(),
            accessKey: $this->s3AccessKey(),
            secretKey: $this->s3SecretKey(),
            useSsl: false,
        );
    }
}
