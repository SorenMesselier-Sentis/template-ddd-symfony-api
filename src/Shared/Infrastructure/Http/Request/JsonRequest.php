<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Request;

use App\Shared\Domain\Exception\MissingFieldException;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class JsonRequest
{
    protected array $data;

    public function __construct(RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();
        $this->data = json_decode($request->getContent(), true) ?? [];

        $this->validate();
    }

    /**
     * @return void
     */
    abstract protected function rules(): array;

    private function validate(): void
    {
        foreach ($this->rules() as $field => $required) {
            if ($required && empty($this->data[$field])) {
                throw new MissingFieldException(
                    sprintf('Field "%s" is required.', $field)
                );
            }
        }
    }
}
