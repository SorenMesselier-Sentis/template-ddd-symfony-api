<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class UpdateWebhookSubscriptionRequest extends JsonRequest
{
    protected function rules(): array
    {
        return [
            'name' => ['required' => true, 'type' => 'string'],
            'url' => ['required' => true, 'type' => 'string'],
        ];
    }

    public function name(): string
    {
        return self::assertString($this->data['name'] ?? null, 'name');
    }

    public function url(): string
    {
        return self::assertString($this->data['url'] ?? null, 'url');
    }

    /**
     * @return list<string>
     */
    public function eventNames(): array
    {
        return self::assertStringList($this->data['event_names'] ?? null, 'event_names');
    }
}
