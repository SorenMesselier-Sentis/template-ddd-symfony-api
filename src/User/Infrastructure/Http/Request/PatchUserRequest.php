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
            'password' => false,
        ];
    }

    public function firstName(): ?string
    {
        return $this->data['firstName'] ?? null;
    }

    public function lastName(): ?string
    {
        return $this->data['lastName'] ?? null;
    }

    public function email(): ?string
    {
        return $this->data['email'] ?? null;
    }

    public function password(): ?string
    {
        return $this->data['password'] ?? null;
    }

    public function isEmpty(): bool
    {
        return null === $this->firstName()
            && null === $this->lastName()
            && null === $this->email()
            && null === $this->password();
    }
}
