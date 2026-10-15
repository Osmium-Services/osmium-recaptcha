<?php

declare(strict_types=1);

namespace Osmium\Services\Recaptcha\Models;

/**
 * Google reCAPTCHA v3 configuration + server-side token verification.
 *
 * File-based config (app/config/services/recaptcha.json.php), matching the
 * Xero/Stripe/PayPal/Analytics/Turnstile convention. verify() is the
 * callable a form handler uses directly - this service has no admin-facing
 * "protect this form" toggle, a form either calls verify() or it doesn't.
 * Same verify(string): bool contract as Turnstile's, so a form handler can
 * switch providers without changing its own code.
 */
class RecaptchaConfig
{
    private const SITEVERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private static ?object $config = null;
    private static string $configPath = 'app/config/services/recaptcha.json.php';

    public static function get(): object
    {
        $configLoaded = self::$config !== null;
        if ($configLoaded) return self::$config;

        $configFile = self::$configPath;

        $configExists = \file_exists($configFile);
        if (!$configExists) {
            self::$config = self::defaults();
            return self::$config;
        }

        $content = \file_get_contents($configFile);
        $jsonStart = \strpos(haystack: $content, needle: '{');

        $noJsonFound = $jsonStart === false;
        if ($noJsonFound) {
            self::$config = self::defaults();
            return self::$config;
        }

        $json = \substr(string: $content, offset: $jsonStart);
        $decoded = \json_decode($json);

        self::$config = $decoded->recaptcha ?? self::defaults();

        return self::$config;
    }

    public static function clearCache(): void
    {
        self::$config = null;
    }

    /**
     * Verify a submitted token against Google's siteverify endpoint and check its score against the configured
     * threshold. Unlike Turnstile's pass/fail, v3 returns a 0.0-1.0 bot-likelihood score - below the threshold is
     * treated as a failed verification here so callers get the same simple bool contract.
     */
    public static function verify(string $token): bool
    {
        return self::check($token) === RecaptchaOutcome::PASSED;
    }

    /**
     * Ask Google about a token and say what came of it (a RecaptchaOutcome constant). Google's own error codes (a wrong
     * secret key, a hostname that does not match) and a failing score go to the error log.
     */
    public static function check(string $token): string
    {
        $config = self::get();
        $threshold = (float) ($config->scoreThreshold ?? 0.5);

        $noSecret = empty($config->secretKey ?? '');
        if ($noSecret) return RecaptchaOutcome::classify(token: $token, response: null, threshold: $threshold);

        $noToken = $token === '';
        if ($noToken) return RecaptchaOutcome::MISSING;

        $response = self::askGoogle(secretKey: (string) $config->secretKey, token: $token);

        $outcome = RecaptchaOutcome::classify(token: $token, response: $response, threshold: $threshold);
        $bad = $outcome !== RecaptchaOutcome::PASSED;
        if ($bad) \error_log('reCAPTCHA check ' . $outcome . ': ' . \json_encode($response['error-codes'] ?? ['score' => $response['score'] ?? null, 'threshold' => $threshold]));

        return $outcome;
    }

    /** @return ?array<string, mixed> Google's decoded answer, or null when it could not be asked */
    private static function askGoogle(string $secretKey, string $token): ?array
    {
        $ch = \curl_init(self::SITEVERIFY_URL);
        \curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \http_build_query([
                'secret' => $secretKey,
                'response' => $token,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $body = \curl_exec($ch);
        $curlError = \curl_error($ch);
        \curl_close($ch);
        if ($curlError || !\is_string($body)) return null;

        $decoded = \json_decode($body, associative: true);

        return \is_array($decoded) ? $decoded : null;
    }

    private static function defaults(): object
    {
        return (object) [
            'enabled' => false,
            'siteKey' => '',
            'secretKey' => '',
            'scoreThreshold' => 0.5,
        ];
    }
}
