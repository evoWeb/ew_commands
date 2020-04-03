<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Evoweb console',
    'description' => 'Commands needed in deployment',
    'category' => 'plugin',
    'author' => 'Sebastian Fischer',
    'author_email' => 'typo3@evoweb.de',
    'author_company' => 'evoWeb',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-',
        ],
    ],
];
