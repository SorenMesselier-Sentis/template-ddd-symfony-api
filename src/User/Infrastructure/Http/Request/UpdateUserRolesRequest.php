<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Domain\Exception\MissingFieldException;
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
        $roles = $this->data['roles'] ?? [];

        if (!is_array($roles) || empty($roles)) {
            throw new MissingFieldException('Field "roles" must be a non-empty array.');
        }

        $result = [];
        foreach ($roles as $role) {
            if (!is_string($role)) {
                throw new MissingFieldException('Field "roles" must contain only strings.');
            }

            $result[] = $role;
        }

        return $result;
    }
}
