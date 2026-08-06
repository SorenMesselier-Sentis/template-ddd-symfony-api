<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class PatchTaskRequest extends JsonRequest
{
    protected function rules(): array
    {
        return [
            'title' => false,
            'status' => false,
            'assigneeId' => ['required' => false, 'type' => 'uuid'],
        ];
    }

    public function title(): ?string
    {
        return self::assertOptionalString($this->data['title'] ?? null, 'title');
    }

    public function status(): ?string
    {
        return self::assertOptionalString($this->data['status'] ?? null, 'status');
    }

    public function assigneeId(): ?string
    {
        return self::assertOptionalString($this->data['assigneeId'] ?? null, 'assigneeId');
    }

    public function isEmpty(): bool
    {
        return null === $this->title() && null === $this->status() && null === $this->assigneeId();
    }
}
