<?php

declare(strict_types=1);

namespace Jambo\Theme\Form\FormElements;

use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;

/**
 * A hidden field carrying the moment the form was rendered, signed so it cannot
 * be back-dated. SubmissionGuardValidator reads it to reject a submission that
 * arrives faster than a person could have typed.
 *
 * Deliberately stateless: no session and therefore no cookie, which a public
 * authority would otherwise have to account for.
 */
final class SubmissionGuard extends AbstractFormElement
{
    public const SEPARATOR = '|';

    public function initializeFormElement(): void
    {
        $hashService = GeneralUtility::makeInstance(HashService::class);
        $renderedAt = (string)time();
        $this->setDefaultValue(
            $renderedAt . self::SEPARATOR . $hashService->hmac($renderedAt, self::class)
        );
        parent::initializeFormElement();
    }
}
