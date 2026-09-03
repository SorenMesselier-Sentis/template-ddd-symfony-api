<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Http\Request;

use App\Shared\Domain\Exception\ValidationError;
use App\Shared\Domain\Exception\ValidationException;
use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class CreateApiClientRequest extends JsonRequest
{
    protected function rules(): array
    {
        return [
            'name' => ['required' => true, 'type' => 'string'],
        ];
    }

    public function name(): string
    {
        return self::assertString($this->data['name'] ?? null, 'name');
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        $scopes = $this->data['scopes'] ?? [];

        if (!\is_array($scopes)) {
            throw new ValidationException([new ValidationError(field: 'scopes', code: 'type_mismatch', message: 'Field "scopes" must be an array of strings.')]);
        }

        $strings = [];

        foreach ($scopes as $scope) {
            if (!\is_string($scope)) {
                throw new ValidationException([new ValidationError(field: 'scopes', code: 'type_mismatch', message: 'Field "scopes" must contain only strings.')]);
            }

            $strings[] = $scope;
        }

        return $strings;
    }
}
