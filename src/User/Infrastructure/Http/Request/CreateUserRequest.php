<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class CreateUserRequest extends JsonRequest
{

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
        return $this->data['firstName'];
    }

    public function lastName(): string
    {
        return $this->data['lastName'];
    }

    public function email(): string
    {
        return $this->data['email'];
    }

    public function password(): string
    {
        return $this->data['password'];
    }
}
