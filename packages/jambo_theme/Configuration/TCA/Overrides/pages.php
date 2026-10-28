<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// The date a notice was published. It sits on the Metadata tab, next to the
// abstract that the notice list shows as its teaser, and only on the standard
// page type - a shortcut or a folder has nothing to publish.
ExtensionManagementUtility::addTCAcolumns('pages', [
    'tx_jambotheme_notice_date' => [
        'exclude' => true,
        'label' => 'Notice date',
        'description' => 'Fill this in on a notice. The newest notices are listed on the home page, this date decides the order.',
        'config' => [
            'type' => 'datetime',
            'format' => 'date',
            'size' => 12,
            'default' => 0,
        ],
    ],
]);

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    'tx_jambotheme_notice_date',
    (string)PageRepository::DOKTYPE_DEFAULT,
    'after:--palette--;;abstract'
);
