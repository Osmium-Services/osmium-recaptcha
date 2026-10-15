<?php

declare(strict_types=1);

namespace Tests\Services\Recaptcha;

use Osmium\Services\Recaptcha\Models\RecaptchaOutcome;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/RecaptchaOutcome.php';

/**
 * What a reCAPTCHA v3 check came to, and what a form does about it.
 */
final class RecaptchaOutcomeTest extends TestCase
{
    public function testNoTokenIsMissing(): void
    {
        $this->assertSame(RecaptchaOutcome::MISSING, RecaptchaOutcome::classify(token: '', response: ['success' => true, 'score' => 0.9], threshold: 0.5));
    }

    public function testNoAnswerFromGoogleIsUnreachable(): void
    {
        $this->assertSame(RecaptchaOutcome::UNREACHABLE, RecaptchaOutcome::classify(token: 'abc', response: null, threshold: 0.5));
    }

    public function testAnAnswerWithoutASuccessFieldIsUnreachable(): void
    {
        $this->assertSame(RecaptchaOutcome::UNREACHABLE, RecaptchaOutcome::classify(token: 'abc', response: ['oops' => 1], threshold: 0.5));
    }

    public function testAHighScoreIsPassed(): void
    {
        $this->assertSame(RecaptchaOutcome::PASSED, RecaptchaOutcome::classify(token: 'abc', response: ['success' => true, 'score' => 0.9], threshold: 0.5));
    }

    public function testTheThresholdItselfPasses(): void
    {
        $this->assertSame(RecaptchaOutcome::PASSED, RecaptchaOutcome::classify(token: 'abc', response: ['success' => true, 'score' => 0.5], threshold: 0.5));
    }

    public function testALowScoreIsFailed(): void
    {
        $this->assertSame(RecaptchaOutcome::FAILED, RecaptchaOutcome::classify(token: 'abc', response: ['success' => true, 'score' => 0.1], threshold: 0.5));
    }

    public function testASuccessWithNoScoreIsFailed(): void
    {
        $this->assertSame(RecaptchaOutcome::FAILED, RecaptchaOutcome::classify(token: 'abc', response: ['success' => true], threshold: 0.5));
    }

    public function testARefusedTokenIsFailedWhateverTheScore(): void
    {
        $this->assertSame(RecaptchaOutcome::FAILED, RecaptchaOutcome::classify(token: 'abc', response: ['success' => false, 'score' => 0.9, 'error-codes' => ['timeout-or-duplicate']], threshold: 0.5));
    }

    public function testFormsStopOnlyOnFailedOrMissing(): void
    {
        $this->assertTrue(RecaptchaOutcome::verdict(RecaptchaOutcome::PASSED));
        $this->assertFalse(RecaptchaOutcome::verdict(RecaptchaOutcome::FAILED));
        $this->assertFalse(RecaptchaOutcome::verdict(RecaptchaOutcome::MISSING));
    }

    public function testAnUnreachableGoogleFailsOpen(): void
    {
        $this->assertNull(RecaptchaOutcome::verdict(RecaptchaOutcome::UNREACHABLE));
    }
}
