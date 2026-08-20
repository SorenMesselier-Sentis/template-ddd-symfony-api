<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class SendTestRealtimeNotificationRequest extends JsonRequest
{
    private const string DEFAULT_MESSAGE = 'Hello from Mercure!';

    /** @return array<string, bool|array{required?: bool, type?: string}> */
    protected function rules(): array
    {
        return [
            'message' => ['required' => false, 'type' => 'string'],
        ];
    }

    public function message(): string
    {
        $value = $this->data['message'] ?? null;

        return \is_string($value) && '' !== $value ? $value : self::DEFAULT_MESSAGE;
    }
}
