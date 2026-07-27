<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class ChangePasswordRequest extends JsonRequest
{
    /** @return array<string, bool> */
    protected function rules(): array
    {
        return [
            'currentPassword' => true,
            'newPassword' => true,
        ];
    }

    public function currentPassword(): string
    {
        return self::assertString($this->data['currentPassword'] ?? null, 'currentPassword');
    }

    public function newPassword(): string
    {
        return self::assertString($this->data['newPassword'] ?? null, 'newPassword');
    }
}
