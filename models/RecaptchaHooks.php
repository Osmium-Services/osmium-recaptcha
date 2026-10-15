<?php

declare(strict_types=1);

namespace Osmium\Services\Recaptcha\Models;

/**
 * The handlers for the core form hooks (form.verify, form.widget). A form that fires those hooks is protected while
 * reCAPTCHA is enabled, and never has to name it.
 */
class RecaptchaHooks
{
    /**
     * form.verify: true when Google scored the visitor as human enough, false when it did not or sent no token, null
     * when this service is off or Google could not be asked (the form then relies on its own checks).
     *
     * @param array{form: string, post: array, dataSource: object} $payload
     */
    public static function verify(array $payload): ?bool
    {
        $off = !(RecaptchaConfig::get()->enabled ?? false);
        if ($off) return null;

        return RecaptchaOutcome::verdict(RecaptchaConfig::check((string) ($payload['post']['recaptcha-token'] ?? '')));
    }

    /**
     * form.widget: the hidden field the head script fills in, or null when this service is off.
     *
     * @param array{form: string} $payload
     */
    public static function widget(array $payload): ?string
    {
        $config = RecaptchaConfig::get();
        $notUsable = !($config->enabled ?? false) || empty($config->siteKey ?? '');
        if ($notUsable) return null;

        return RecaptchaWidget::html();
    }
}
