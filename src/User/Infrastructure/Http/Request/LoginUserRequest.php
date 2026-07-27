<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class LoginUserRequest extends JsonRequest
{
    /** @return array<string, bool> */
    protected function rules(): array
    {
        return [
            'email' => true,
            'password' => true,
        ];
    }

    public function email(): string
    {
        return self::assertString($this->data['email'] ?? null, 'email');
    }

    public function password(): string
    {
        return self::assertString($this->data['password'] ?? null, 'password');
    }
}
