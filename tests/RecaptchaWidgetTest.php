<?php

declare(strict_types=1);

namespace Tests\Services\Recaptcha;

use Osmium\Services\Recaptcha\Models\RecaptchaWidget;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/RecaptchaWidget.php';

/**
 * The only thing a form needs for v3: the hidden field the head script fills in.
 */
final class RecaptchaWidgetTest extends TestCase
{
    public function test_the_widget_is_the_hidden_token_field_the_head_script_looks_for(): void
    {
        $html = RecaptchaWidget::html();

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('id="recaptcha-token"', $html);
        $this->assertStringContainsString('name="recaptcha-token"', $html);
    }
}
