<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class LogoutUserRequest extends JsonRequest
{
    public function rules(): array
    {
        return [
            'refresh_token' => true,
        ];
    }

    public function refreshToken(): string
    {
        return self::assertString($this->data['refresh_token'] ?? null, 'refresh_token');
    }
}
