<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class UpdateUserRolesRequest extends JsonRequest
{
    protected function rules(): array
    {
        return [
            'roles' => true,
        ];
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return self::assertStringList($this->data['roles'] ?? null, 'roles');
    }
}
