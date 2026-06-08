<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Request;

use App\Shared\Domain\Exception\ValidationException;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class JsonRequestTest extends UnitTestCase
{
    public function testCollectsAllRequiredFieldErrors(): void
    {
        try {
            $this->createJsonRequest(['email' => 'a@b.com'], [
                'firstName' => true,
                'lastName' => true,
                'email' => true,
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertCount(2, $e->errors());
            $codes = array_map(static fn ($error) => $error->code, $e->errors());
            $this->assertSame(['required', 'required'], $codes);
        }
    }

    public function testInvalidEmailProducesInvalidEmailCode(): void
    {
        try {
            $this->createJsonRequest(['email' => 'not-an-email'], ['email' => ['required' => true, 'type' => 'email']]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame('invalid_email', $e->errors()[0]->code);
        }
    }

    public function testTypeMismatchForIntField(): void
    {
        try {
            $this->createJsonRequest(['count' => 'abc'], ['count' => ['type' => 'int']]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame('type_mismatch', $e->errors()[0]->code);
        }
    }

    /**
     * @param array<string, mixed>                                      $body
     * @param array<string, bool|array{required?: bool, type?: string}> $rules
     */
    private function createJsonRequest(array $body, array $rules): object
    {
        $httpRequest = Request::create('/', 'POST', [], [], [], [], json_encode($body, JSON_THROW_ON_ERROR));
        $stack = new RequestStack();
        $stack->push($httpRequest);

        return new class($stack, $rules) extends \App\Shared\Infrastructure\Http\Request\JsonRequest {
            /** @param array<string, bool|array{required?: bool, type?: string}> $rules */
            public function __construct(RequestStack $stack, private readonly array $rules)
            {
                parent::__construct($stack);
            }

            protected function rules(): array
            {
                return $this->rules;
            }
        };
    }
}
