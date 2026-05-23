<?php

declare(strict_types=1);

namespace App\Shared\Domain\Email;

interface EmailTemplateRendererInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): RenderedEmailContent;
}
