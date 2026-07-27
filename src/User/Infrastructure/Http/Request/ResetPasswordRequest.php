<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class ResetPasswordRequest extends JsonRequest
{
    /** @return array<string, bool> */
    protected function rules(): array
    {
        return [
            'token' => true,
            'password' => true,
        ];
    }

    public function token(): string
    {
        return self::assertString($this->data['token'] ?? null, 'token');
    }

    public function password(): string
    {
        return self::assertString($this->data['password'] ?? null, 'password');
    }
}
