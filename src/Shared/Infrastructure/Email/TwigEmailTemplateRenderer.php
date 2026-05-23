<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Email;

use App\Shared\Domain\Email\EmailTemplateRendererInterface;
use App\Shared\Domain\Email\RenderedEmailContent;
use Twig\Environment;

final class TwigEmailTemplateRenderer implements EmailTemplateRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function render(string $template, array $context = []): RenderedEmailContent
    {
        $basePath = sprintf('email/%s', $template);

        return new RenderedEmailContent(
            subject: trim($this->twig->render(sprintf('%s.subject.twig', $basePath), $context)),
            textBody: trim($this->twig->render(sprintf('%s.txt.twig', $basePath), $context)),
            htmlBody: trim($this->twig->render(sprintf('%s.html.twig', $basePath), $context)),
        );
    }
}
