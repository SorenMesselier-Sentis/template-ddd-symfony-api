<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class PutProjectRequest extends JsonRequest
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
}
