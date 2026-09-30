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
     * Verify a submitted token against Google's siteverify endpoint and
     * check its score against the configured threshold. Unlike Turnstile's
     * pass/fail, v3 returns a 0.0-1.0 bot-likelihood score - below the
     * threshold is treated as a failed verification here so callers get the
     * same simple bool contract.
     */
    public static function verify(string $token): bool
    {
        $config = self::get();

        $tokenMissing = $token === '' || empty($config->secretKey ?? '');
        if ($tokenMissing) return false;

        $ch = \curl_init(self::SITEVERIFY_URL);
        \curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \http_build_query([
                'secret' => $config->secretKey,
                'response' => $token,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = \curl_exec($ch);
        $curlError = \curl_error($ch);
        if ($curlError) return false;

        $decoded = \json_decode($response, associative: true);

        $succeeded = (bool) ($decoded['success'] ?? false);
        if (!$succeeded) return false;

        $score = (float) ($decoded['score'] ?? 0.0);
        $threshold = (float) ($config->scoreThreshold ?? 0.5);

        return $score >= $threshold;
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
