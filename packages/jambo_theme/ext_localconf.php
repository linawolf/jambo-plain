<?php

declare(strict_types=1);

defined('TYPO3') or die();

// TYPO3 only knows the locales in its own registry, and Swahili is not among
// them: without this the label files are never looked up and the chrome stays
// English. Both keys are needed - the base language carries the label file
// lookup, and the country variant is what Locales::createLocale() is asked for
// with the site language "sw_KE.UTF-8"; without it every Swahili request logs
// 'Locale "sw_KE.UTF-8" and "sw_KE" not found.'
$GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user']['sw'] = 'Swahili';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user']['sw_KE'] = 'Swahili (Kenya)';

// EXT:form registers its plugin with only the "perform" action non-cacheable,
// so the rendered form comes out of the page cache: every visitor receives the
// same hidden timestamp, and SubmissionGuardValidator would measure the age of
// the cache entry instead of the age of the visit. Re-registering with "render"
// non-cacheable too makes the form render per request.
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Form',
    'Formframework',
    [\TYPO3\CMS\Form\Controller\FormFrontendController::class => ['render', 'perform']],
    [\TYPO3\CMS\Form\Controller\FormFrontendController::class => ['render', 'perform']]
);

