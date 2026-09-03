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

    public const PROJECT_JOHN_WEBSITE_ID = '55555555-5555-4555-8555-555555555555';
    public const PROJECT_JOHN_WEBSITE_NAME = 'Website Redesign';

    // Assigned to Jane, attached to John's invoice document — demonstrates the
    // Project BC's cross-BC UUID references (assigneeId -> User, attachmentId -> Document).
    public const TASK_JOHN_WEBSITE_DESIGN_ID = '66666666-6666-4666-8666-666666666666';
    public const TASK_JOHN_WEBSITE_DESIGN_TITLE = 'Design the homepage mockup';

    public const API_CLIENT_TEST_ID = '77777777-7777-4777-8777-777777777777';
    public const API_CLIENT_TEST_NAME = 'Test Worker';
    // Plain-text secret for the fixture client — hashed at load time, known here only so
    // HTTP tests can request a token for it (see ApiClientFixture).
    public const API_CLIENT_TEST_SECRET = 'test-client-secret-do-not-use-in-prod';
    public const API_CLIENT_TEST_SCOPE = 'documents:write';

    public const WEBHOOK_SUBSCRIPTION_TEST_ID = '88888888-8888-4888-8888-888888888888';
    public const WEBHOOK_SUBSCRIPTION_TEST_NAME = 'Test Subscription';
    public const WEBHOOK_SUBSCRIPTION_TEST_URL = 'https://webhook.example.test/inbound';
    public const WEBHOOK_SUBSCRIPTION_TEST_SECRET = 'test-webhook-signing-secret-do-not-use-in-prod';
    public const WEBHOOK_SUBSCRIPTION_TEST_EVENT_NAME = 'api_client.created';
}
