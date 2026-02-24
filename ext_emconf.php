<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'View Tracker GeoIP',
    'description' => 'Country enrichment for view-tracker via GeoIP lookup (b13/magnets)',
    'category' => 'misc',
    'author' => 'b13 GmbH',
    'author_email' => 'typo3@b13.com',
    'author_company' => 'b13 GmbH',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => ['typo3' => '13.4.0-14.99.99', 'view_tracker' => '1.0.0-1.99.99'],
        'conflicts' => [],
        'suggests' => [],
    ],
];
