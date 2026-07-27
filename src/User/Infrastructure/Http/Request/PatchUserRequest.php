<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class PatchUserRequest extends JsonRequest
{
    /** @return array<string, bool> */
    protected function rules(): array
    {
        return [
            'firstName' => false,
            'lastName' => false,
            'email' => false,
        ];
    }

    public function firstName(): ?string
    {
        return self::assertOptionalString($this->data['firstName'] ?? null, 'firstName');
    }

    public function lastName(): ?string
    {
        return self::assertOptionalString($this->data['lastName'] ?? null, 'lastName');
    }

    public function email(): ?string
    {
        return self::assertOptionalString($this->data['email'] ?? null, 'email');
    }

    public function isEmpty(): bool
    {
        return null === $this->firstName()
            && null === $this->lastName()
            && null === $this->email();
    }
}
