<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Controller;

use App\Shared\Infrastructure\Http\Controller\LivenessController;
use App\Shared\Infrastructure\Http\Response\ApiResponse;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Serializer\SerializerInterface;

final class LivenessControllerTest extends UnitTestCase
{
    public function testItReturns200WithOkStatus(): void
    {
        $controller = new LivenessController($this->apiResponse());

        $response = $controller();

        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['data' => ['status' => 'ok']], $payload);
    }

    private function apiResponse(): ApiResponse
    {
        $serializer = $this->createStub(SerializerInterface::class);
        $serializer
            ->method('serialize')
            ->willReturnCallback(static fn (mixed $data): string => json_encode($data, JSON_THROW_ON_ERROR));

        return new ApiResponse($serializer);
    }
}
