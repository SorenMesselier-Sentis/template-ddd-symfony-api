<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class PostMeConsentRequest extends JsonRequest
{
    /** @return array<string, bool|array{required?: bool, type?: string}> */
    protected function rules(): array
    {
        return [
            'type' => ['required' => true, 'type' => 'string'],
            'version' => ['required' => true, 'type' => 'string'],
        ];
    }

    public function type(): string
    {
        return self::assertString($this->data['type'] ?? null, 'type');
    }

    public function version(): string
    {
        return self::assertString($this->data['version'] ?? null, 'version');
    }
}
