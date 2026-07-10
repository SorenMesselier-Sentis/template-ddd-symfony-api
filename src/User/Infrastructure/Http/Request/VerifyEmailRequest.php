<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class VerifyEmailRequest extends JsonRequest
{
    /** @return array<string, bool> */
    protected function rules(): array
    {
        return ['token' => true];
    }

    public function token(): string
    {
        return $this->data['token'];
    }
}
