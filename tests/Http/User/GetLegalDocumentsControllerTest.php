<?php

declare(strict_types=1);

namespace App\Tests\Http\User;

use App\Tests\Http\HttpTestCase;
use App\User\Infrastructure\Legal\LegalDocumentVersion;

final class GetLegalDocumentsControllerTest extends HttpTestCase
{
    public function testItIsPubliclyAccessibleAndReturnsCurrentVersions(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $client->request('GET', '/api/v1/legal/documents');

        $this->assertJsonEnvelope($client->getResponse(), 200);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(LegalDocumentVersion::TERMS_OF_SERVICE, $payload['data']['terms_of_service']['version']);
        $this->assertSame(LegalDocumentVersion::PRIVACY_POLICY, $payload['data']['privacy_policy']['version']);
    }
}
