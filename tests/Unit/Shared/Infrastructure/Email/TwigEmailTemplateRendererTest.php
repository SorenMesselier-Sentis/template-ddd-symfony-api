<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Email;

use App\Shared\Infrastructure\Email\TwigEmailTemplateRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class TwigEmailTemplateRendererTest extends TestCase
{
    #[Test]
    public function it_renders_subject_text_and_html_templates(): void
    {
        $renderer = new TwigEmailTemplateRenderer(new Environment(new ArrayLoader([
            'email/user/welcome.subject.twig' => 'Welcome {{ firstName }}!',
            'email/user/welcome.txt.twig' => 'Hello {{ firstName }} {{ lastName }}',
            'email/user/welcome.html.twig' => '<p>Hello {{ firstName }}</p>',
        ])));

        $content = $renderer->render('user/welcome', [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ]);

        self::assertSame('Welcome Jane!', $content->subject());
        self::assertSame('Hello Jane Doe', $content->textBody());
        self::assertSame('<p>Hello Jane</p>', $content->htmlBody());
    }
}
