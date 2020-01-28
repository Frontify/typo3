<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2019-12-17 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Frontify integration for Typo',
    'description' => 'Use Frontify Assets in typo3.',
    'category' => 'distribution',
    'author' => 'Leo Studer',
    'author_company' => 'Frontify',
    'author_email' => 'leo.studer@frontify.com',
    'state' => 'beta',
    'clearCacheOnLoad' => true,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Frontify\\Typo3\\' => 'Classes'
        ],
    ],
];