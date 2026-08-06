<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class PatchProjectRequest extends JsonRequest
{
    protected function rules(): array
    {
        return [
            'name' => false,
            'status' => false,
        ];
    }

    public function name(): ?string
    {
        return self::assertOptionalString($this->data['name'] ?? null, 'name');
    }

    public function status(): ?string
    {
        return self::assertOptionalString($this->data['status'] ?? null, 'status');
    }

    public function isEmpty(): bool
    {
        return null === $this->name() && null === $this->status();
    }
}
