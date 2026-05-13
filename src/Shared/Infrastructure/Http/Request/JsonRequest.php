<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Request;

use App\Shared\Domain\Exception\InvalidJsonException;
use App\Shared\Domain\Exception\MissingFieldException;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class JsonRequest
{
    /** @var array<string, mixed> */
    protected array $data;

    public function __construct(
        RequestStack $requestStack,
    ) {
        $request = $requestStack->getCurrentRequest();
        $content = $request->getContent();

        if (empty($content)) {
            throw new MissingFieldException('Request body is empty.');
        }

        $decoded = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidJsonException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }

        if (!\is_array($decoded)) {
            throw new InvalidJsonException('JSON body must be a JSON object.');
        }

        $this->data = $decoded;
        $this->validate();
    }

    /** @return array<string, bool> */
    abstract protected function rules(): array;

    private function validate(): void
    {
        foreach ($this->rules() as $field => $required) {
            if ($required && empty($this->data[$field])) {
                throw new MissingFieldException(sprintf('Field "%s" is required.', $field));
            }
        }
    }
}
