<?php

declare(strict_types=1);

namespace Osmium\Services\Recaptcha\Models;

/**
 * What a reCAPTCHA v3 check came to, and what a form should do about it. Pure: no network, no database.
 */
class RecaptchaOutcome
{
    public const PASSED = 'passed';
    public const FAILED = 'failed'; // Google refused the token, or the score is under the threshold
    public const MISSING = 'missing'; // The visitor sent no token: the script did not load or the request is not from a browser
    public const UNREACHABLE = 'unreachable'; // Google could not be asked, or its answer made no sense

    /**
     * @param ?array<string, mixed> $response Google's decoded answer, null when there was none
     * @param float $threshold The lowest score (0.0 bot to 1.0 human) that passes
     */
    public static function classify(string $token, ?array $response, float $threshold): string
    {
        $noToken = $token === '';
        if ($noToken) return self::MISSING;

        $noAnswer = $response === null || !\array_key_exists('success', $response);
        if ($noAnswer) return self::UNREACHABLE;

        $refused = $response['success'] !== true;
        if ($refused) return self::FAILED;

        $score = (float) ($response['score'] ?? 0.0);

        return $score >= $threshold ? self::PASSED : self::FAILED;
    }

    /**
     * The answer to the form.verify hook: false stops the form. An unreachable Google fails open (null), so an outage
     * cannot lose a customer's submission; the form's own checks still apply.
     */
    public static function verdict(string $outcome): ?bool
    {
        return match ($outcome) {
            self::PASSED => true,
            self::FAILED, self::MISSING => false,
            default => null,
        };
    }
}
