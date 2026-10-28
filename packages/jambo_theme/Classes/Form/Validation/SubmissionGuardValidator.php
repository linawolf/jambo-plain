<?php

declare(strict_types=1);

namespace Jambo\Theme\Form\Validation;

use Jambo\Theme\Form\FormElements\SubmissionGuard;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface as SymfonyRateLimiterFactoryInterface;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\RateLimiter\RateLimiterFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Two cheap checks against automated submissions, neither of which costs a
 * visitor anything:
 *
 * 1. the form has to have been on screen for a moment before it comes back
 * 2. one address may only submit so often
 *
 * Both answer with an ordinary validation error, so the message is shown in the
 * form the same way a missing field is.
 */
final class SubmissionGuardValidator extends AbstractValidator
{
    protected $supportedOptions = [
        'minimumSeconds' => [4, 'Seconds the form must have been on screen', 'integer'],
        'maximumPerHour' => [5, 'Submissions allowed per address and hour', 'integer'],
        'bucket' => ['form', 'Name of the rate limit counter; forms with the same name share it', 'string'],
    ];

    public function isValid(mixed $value): void
    {
        if (!$this->hasPlausibleTiming((string)$value)) {
            // Deliberately vague: a bot author should not learn which check bit.
            $this->addError(
                $this->translateErrorMessage('validation.submissionGuard', 'jambo_theme')
                    ?: 'Your message could not be sent. Please try again.',
                1761638400
            );
            return;
        }

        if (!$this->isWithinRateLimit()) {
            $this->addError(
                $this->translateErrorMessage('validation.rateLimit', 'jambo_theme')
                    ?: 'Too many messages have been sent from here. Please try again later.',
                1761638401
            );
        }
    }

    private function hasPlausibleTiming(string $value): bool
    {
        $parts = explode(SubmissionGuard::SEPARATOR, $value, 2);
        if (count($parts) !== 2) {
            return false;
        }
        [$renderedAt, $signature] = $parts;

        $hashService = GeneralUtility::makeInstance(HashService::class);
        if (!hash_equals($hashService->hmac($renderedAt, SubmissionGuard::class), $signature)) {
            return false;
        }

        $elapsed = time() - (int)$renderedAt;
        // A negative value means a clock that was played with.
        return $elapsed >= (int)$this->options['minimumSeconds'] && $elapsed >= 0;
    }

    private function isWithinRateLimit(): bool
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return true;
        }

        $limiter = GeneralUtility::makeInstance(RateLimiterFactory::class)->createRequestBasedLimiter(
            $request,
            [
                // Each form counts on its own: reporting a missed collection
                // must not be blocked by messages sent through another form.
                'id' => 'jambo-form-' . $this->options['bucket'],
                'policy' => 'sliding_window',
                'limit' => (int)$this->options['maximumPerHour'],
                'interval' => '1 hour',
            ]
        );

        return $limiter->consume()->isAccepted();
    }
}
