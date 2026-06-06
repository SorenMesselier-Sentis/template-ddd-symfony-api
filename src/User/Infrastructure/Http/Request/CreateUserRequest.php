<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class CreateUserRequest extends JsonRequest
{
    /** @return array<string, bool|array{required?: bool, type?: string}> */
    protected function rules(): array
    {
        return [
            'firstName' => ['required' => true, 'type' => 'string'],
            'lastName' => ['required' => true, 'type' => 'string'],
            'email' => ['required' => true, 'type' => 'email'],
            'password' => ['required' => true, 'type' => 'string'],
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
