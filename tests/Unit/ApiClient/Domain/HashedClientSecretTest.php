<?php

declare(strict_types=1);

namespace App\Tests\Unit\ApiClient\Domain;

use App\ApiClient\Domain\ValueObject\HashedClientSecret;
use App\Tests\Unit\UnitTestCase;

final class HashedClientSecretTest extends UnitTestCase
{
    public function testVerifyReturnsTrueForTheCorrectPlainSecret(): void
    {
        $hash = HashedClientSecret::fromPlainSecret('correct-secret');

        $this->assertTrue($hash->verify('correct-secret'));
        $this->assertFalse($hash->verify('wrong-secret'));
    }

    public function testFromHashRoundTripsAnExistingHash(): void
    {
        $original = HashedClientSecret::fromPlainSecret('correct-secret');
        $reloaded = HashedClientSecret::fromHash($original->value());

        $this->assertTrue($reloaded->verify('correct-secret'));
    }
}
