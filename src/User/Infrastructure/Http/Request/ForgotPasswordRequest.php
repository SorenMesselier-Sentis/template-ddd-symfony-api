<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Request;

use App\Shared\Infrastructure\Http\Request\JsonRequest;

final class ForgotPasswordRequest extends JsonRequest
{
    /** @return array<string, bool> */
    protected function rules(): array
    {
        return ['email' => true];
    }

    public function email(): string
    {
        return $this->data['email'];
    }
}
