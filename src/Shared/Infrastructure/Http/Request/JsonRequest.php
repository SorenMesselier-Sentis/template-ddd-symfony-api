<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Request;

use App\Shared\Domain\Exception\InvalidJsonException;
use App\Shared\Domain\Exception\ValidationError;
use App\Shared\Domain\Exception\ValidationException;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class JsonRequest
{
    use RequestTypeAssertTrait;

    /** @var array<string, mixed> */
    protected array $data;

    public function __construct(
        RequestStack $requestStack,
    ) {
        $request = $requestStack->getCurrentRequest();

        if (null === $request) {
            throw new InvalidJsonException('No active HTTP request.');
        }

        $content = $request->getContent();

        if ('' === $content) {
            $this->data = [];
            $this->validate();

            return;
        }

        $decoded = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidJsonException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }

        if (!\is_array($decoded)) {
            throw new InvalidJsonException('JSON body must be a JSON object.');
        }

        $this->data = self::normalizeDecodedPayload($decoded);
        $this->validate();
    }

    /**
     * @param array<mixed, mixed> $decoded
     *
     * @return array<string, mixed>
     */
    private static function normalizeDecodedPayload(array $decoded): array
    {
        $data = [];

        foreach ($decoded as $key => $value) {
            if (!\is_string($key)) {
                throw new InvalidJsonException('JSON body must be a JSON object.');
            }

            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * @return array<string, bool|array{required?: bool, type?: string}>
     */
    abstract protected function rules(): array;

    private function validate(): void
    {
        /** @var list<ValidationError> $errors */
        $errors = [];

        foreach ($this->rules() as $field => $rule) {
            $normalized = $this->normalizeRule($rule);
            $value = $this->data[$field] ?? null;
            $isEmpty = null === $value || '' === $value || ([] === $value);

            if (($normalized['required'] ?? false) && $isEmpty) {
                $errors[] = new ValidationError(
                    field: $field,
                    code: 'required',
                    message: sprintf('Field "%s" is required.', $field),
                );

                continue;
            }

            if ($isEmpty || !isset($normalized['type'])) {
                continue;
            }

            $typeError = $this->validateType($field, $value, $normalized['type']);

            if (null !== $typeError) {
                $errors[] = $typeError;
            }
        }

        if ([] !== $errors) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param bool|array{required?: bool, type?: string} $rule
     *
     * @return array{required?: bool, type?: string}
     */
    private function normalizeRule(bool|array $rule): array
    {
        if (\is_bool($rule)) {
            return ['required' => $rule];
        }

        return $rule;
    }

    private function validateType(string $field, mixed $value, string $type): ?ValidationError
    {
        return match ($type) {
            'string' => \is_string($value) ? null : $this->typeMismatch($field, $type),
            'int' => $this->isIntValue($value) ? null : $this->typeMismatch($field, $type),
            'bool' => \is_bool($value) ? null : $this->typeMismatch($field, $type),
            'uuid' => $this->isUuidV4($value) ? null : new ValidationError(
                field: $field,
                code: 'invalid_uuid',
                message: sprintf('Field "%s" must be a valid UUID.', $field),
            ),
            'email' => $this->isEmail($value) ? null : new ValidationError(
                field: $field,
                code: 'invalid_email',
                message: sprintf('Field "%s" must be a valid email address.', $field),
            ),
            default => $this->typeMismatch($field, $type),
        };
    }

    private function isIntValue(mixed $value): bool
    {
        if (\is_int($value)) {
            return true;
        }

        return \is_string($value) && ctype_digit($value);
    }

    private function isUuidV4(mixed $value): bool
    {
        if (!\is_string($value)) {
            return false;
        }

        return 1 === preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    private function isEmail(mixed $value): bool
    {
        return \is_string($value) && false !== filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    private function typeMismatch(string $field, string $type): ValidationError
    {
        return new ValidationError(
            field: $field,
            code: 'type_mismatch',
            message: sprintf('Field "%s" must be of type %s.', $field, $type),
        );
    }
}
