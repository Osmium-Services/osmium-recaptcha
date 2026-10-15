<?php

declare(strict_types=1);

namespace Osmium\Services\Recaptcha\Models;

/**
 * The markup a form gets for reCAPTCHA v3. Pure: no config, no request.
 *
 * v3 has no box. The head script (theme/recaptcha-headscript.phtml) intercepts the submit of any form holding this
 * hidden field, fetches a fresh token and posts it, so the field is all the form needs.
 */
class RecaptchaWidget
{
    public static function html(): string
    {
        return '<input type="hidden" id="recaptcha-token" name="recaptcha-token" value="">';
    }
}
