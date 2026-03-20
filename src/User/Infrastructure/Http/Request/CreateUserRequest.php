<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RequestStack;

final class CreateUserRequest
{
    private array $data;

    public function __construct(RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();
        $this->data = json_decode($request->getContent(), true) ?? [];

        $this->validate();
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

    private function validate(): void
    {
        $required = ['firstName', 'lastName', 'email', 'password'];

        foreach ($required as $field) {
            if (empty($this->data[$field])) {
                throw new BadRequestException(
                    sprintf('Field "%s" is required.', $field)
                );
            }
        }
    }
}
