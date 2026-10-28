<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Registers the "Image slider" content element. addRecordType() adds the CType
// item, its type definition and - because the New Content Element wizard is
// built from the CType items - the wizard entry as well.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
    [
        'label' => 'Image slider',
        'description' => 'Several images in a slider with arrows, pagination, swipe and keyboard control.',
        'value' => 'jambotheme_imageslider',
        'icon' => 'mimetypes-x-content-image',
        'group' => 'default',
    ],
    '
        --palette--;;headers,
    --div--;core.form.tabs:media,
        assets,
    --div--;core.form.tabs:appearance,
        --palette--;;frames,
    --div--;core.form.tabs:categories,
        categories',
    [
        'columnsOverrides' => [
            'assets' => [
                'label' => 'Slides',
                'config' => [
                    'allowed' => 'common-image-types',
                    'minitems' => 1,
                ],
            ],
        ],
    ]
);

// Registers the "Easy Read point" content element: one picture beside one
// short point. Easy Read asks for a supporting image per statement, so the
// image is part of the element rather than something an editor places beside
// it and hopes stays aligned.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
    [
        'label' => 'Easy Read point',
        'description' => 'One short point with a supporting picture, for the Easy Read pages.',
        'value' => 'jambotheme_easyread',
        'icon' => 'mimetypes-x-content-text-picture',
        'group' => 'default',
    ],
    '
        --palette--;;headers,
        bodytext,
    --div--;core.form.tabs:media,
        assets,
    --div--;core.form.tabs:appearance,
        --palette--;;frames',
    [
        'columnsOverrides' => [
            'bodytext' => [
                'config' => [
                    'enableRichtext' => true,
                ],
            ],
            'assets' => [
                'label' => 'Picture',
                'config' => [
                    'allowed' => 'common-image-types',
                    'maxitems' => 1,
                ],
            ],
        ],
    ]
);

// A callout is not a kind of content, it is a way of presenting content, which
// is what frame_class is for: an editor picks "Callout" under Appearance on an
// ordinary text element and keeps the rich text editor, the header field and
// its heading level. The alternative - pasting <div class="callout"> into an
// element of type "Plain HTML" - is how the first build did it, and it asked an
// editor to write markup and to remember a class name.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'frame_class',
    [
        'label' => 'Callout',
        'value' => 'callout',
    ],
    'default',
    'after'
);
