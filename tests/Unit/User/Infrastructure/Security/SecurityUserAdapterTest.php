<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Security;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserIdMother;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Infrastructure\Security\SecurityUserAdapter;

final class SecurityUserAdapterTest extends UnitTestCase
{
    public function testSubjectIdReturnsTheWrappedUsersId(): void
    {
        $id = UserIdMother::random();
        $user = UserMother::create(id: $id);

        $adapter = new SecurityUserAdapter($user);

        $this->assertSame($id->value(), $adapter->subjectId());
    }
}
