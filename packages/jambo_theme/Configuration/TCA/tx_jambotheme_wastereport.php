<?php

declare(strict_types=1);

// A small TCA, so the records get their timestamps, show up in the backend list
// and can be looked at. Nobody is meant to type these by hand - the form writes
// them - so every field is read-only in the backend.
return [
    'ctrl' => [
        'title' => 'Missed waste collection',
        'label' => 'street',
        'label_alt' => 'ward,missed_on',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'crdate DESC',
        'iconfile' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/mimetypes/mimetypes-x-content-form.svg',
        'hideTable' => false,
        'readOnly' => true,
        'searchFields' => 'ward,street,reporter_name,reporter_email,details',
    ],
    'columns' => [
        'crdate' => [
            'label' => 'Reported at',
            'config' => ['type' => 'datetime', 'readOnly' => true],
        ],
        'ward' => ['label' => 'Ward', 'config' => ['type' => 'input', 'size' => 30]],
        'street' => ['label' => 'Street or plot', 'config' => ['type' => 'input', 'size' => 40]],
        'missed_on' => ['label' => 'Collection missed on', 'config' => ['type' => 'input', 'size' => 12]],
        'waste_type' => ['label' => 'Kind of waste', 'config' => ['type' => 'input', 'size' => 20]],
        'details' => ['label' => 'Details', 'config' => ['type' => 'text', 'rows' => 4]],
        'reporter_name' => ['label' => 'Name', 'config' => ['type' => 'input', 'size' => 30]],
        'reporter_email' => ['label' => 'Email', 'config' => ['type' => 'email']],
        'reporter_phone' => ['label' => 'Telephone', 'config' => ['type' => 'input', 'size' => 20]],
        'digest_sent_at' => [
            'label' => 'Included in digest at',
            'config' => ['type' => 'datetime', 'readOnly' => true],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'crdate, ward, street, missed_on, waste_type, details,
                --div--;Reporter, reporter_name, reporter_email, reporter_phone,
                --div--;Digest, digest_sent_at',
        ],
    ],
];
