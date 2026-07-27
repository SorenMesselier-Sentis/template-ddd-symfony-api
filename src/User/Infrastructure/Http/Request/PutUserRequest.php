<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class PutUserRequest extends JsonRequest
{
    /** @return array<string, bool> */
    protected function rules(): array
    {
        return [
            'firstName' => true,
            'lastName' => true,
            'email' => true,
            'password' => true,
        ];
    }

    public function firstName(): string
    {
        return self::assertString($this->data['firstName'] ?? null, 'firstName');
    }

    public function lastName(): string
    {
        return self::assertString($this->data['lastName'] ?? null, 'lastName');
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
