<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixture;

final class FixtureData
{
    public const DEFAULT_PASSWORD = 'secret1234';

    public const USER_JOHN_ID = '11111111-1111-4111-8111-111111111111';
    public const USER_JOHN_FIRST_NAME = 'John';
    public const USER_JOHN_LAST_NAME = 'Doe';
    public const USER_JOHN_EMAIL = 'john.doe@example.com';

    public const USER_JANE_ID = '22222222-2222-4222-8222-222222222222';
    public const USER_JANE_FIRST_NAME = 'Jane';
    public const USER_JANE_LAST_NAME = 'Doe';
    public const USER_JANE_EMAIL = 'jane.doe@example.com';

    public const USER_BOB_ID = '33333333-3333-4333-8333-333333333333';
    public const USER_BOB_FIRST_NAME = 'Bob';
    public const USER_BOB_LAST_NAME = 'Smith';
    public const USER_BOB_EMAIL = 'bob.smith@example.com';

    public const DOCUMENT_JOHN_INVOICE_ID = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    public const DOCUMENT_JOHN_AVATAR_ID = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
    public const DOCUMENT_JANE_CONTRACT_ID = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
}
