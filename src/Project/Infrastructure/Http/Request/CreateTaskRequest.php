<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class CreateTaskRequest extends JsonRequest
{
    protected function rules(): array
    {
        return [
            'title' => ['required' => true, 'type' => 'string'],
            'assigneeId' => ['required' => false, 'type' => 'uuid'],
            'attachmentId' => ['required' => false, 'type' => 'uuid'],
        ];
    }

    public function title(): string
    {
        return self::assertString($this->data['title'] ?? null, 'title');
    }

    public function assigneeId(): ?string
    {
        return self::assertOptionalString($this->data['assigneeId'] ?? null, 'assigneeId');
    }

    public function attachmentId(): ?string
    {
        return self::assertOptionalString($this->data['attachmentId'] ?? null, 'attachmentId');
    }
}
