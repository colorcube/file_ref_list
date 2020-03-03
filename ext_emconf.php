<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'File>Files on page',
    'description' => 'Listing of files related to page content',
    'category' => 'module',
    'author' => 'René Fritz',
    'author_email' => 'r.fritz@colorcube.de',
    'author_company' => 'Colorcube',
    'version' => '0.1.1',
    'state' => 'beta',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-8.7.99',
            'filelist' => '8.7.0-8.7.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Colorcube\\FileRefList\\' => 'Classes'
        ]
    ]
];
