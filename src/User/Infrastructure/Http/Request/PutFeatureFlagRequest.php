<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class PutFeatureFlagRequest extends JsonRequest
{
    protected function rules(): array
    {
        return [
            'enabled' => ['required' => true, 'type' => 'bool'],
            'description' => ['required' => false, 'type' => 'string'],
        ];
    }

    public function enabled(): bool
    {
        return self::assertBool($this->data['enabled'] ?? null, 'enabled');
    }

    public function description(): ?string
    {
        return self::assertOptionalString($this->data['description'] ?? null, 'description');
    }
}
