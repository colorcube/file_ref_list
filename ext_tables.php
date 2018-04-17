<?php
defined('TYPO3_MODE') or die('Access denied.');

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Colorcube.FileRefList',
        'file',
        'listref',
        '',
        [
            'FileReferencesList' => 'index',
        ],
        [
            'access' => 'user,group',
            'workspaces' => 'online,custom',
            'icon' => 'EXT:file_ref_list/Resources/Public/Icons/module-file_ref_list.svg',
            'labels' => 'LLL:EXT:file_ref_list/Resources/Private/Language/locallang_mod_file_ref_list.xlf',
            'navigationComponentId' => 'typo3-pagetree'
        ]
    );


}
