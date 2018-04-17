<?php
namespace Colorcube\FileRefList\Hook;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class adds Filelist related JavaScript to the backend
 */
class BackendControllerHook
{
    /**
     * Adds Filelist JavaScript used e.g. by context menu
     *
     * @param array $configuration
     * @param BackendController $backendController
     */
    public function addJavaScript(array $configuration, BackendController $backendController)
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

//        $pageRenderer-> addRequireJsConfiguration([
//            'paths' => [
//                'TYPO3/CMS/Backend/ModuleMenuPatch' => \TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath(
//                        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('file_ref_list', 'Resources/Public/JavaScript/')
//                    ) . 'ModuleMenuPatch'
//            ]
//        ]);
//        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ModuleMenuPatch');
//
//
//



        // enabling page tree in file module doesn't really work
        // this is an attempt to patch

        $pageRenderer->addJsInlineCode('FileReferencesList', '
setTimeout(function(){
// requirejs doesn\'t work

            require(["TYPO3/CMS/Backend/ModuleMenu"], function(ModuleMenu) {

                TYPO3.ModuleMenu.App.includeId = function (mod, params) {

                    if (typeof mod !== \'string\') {
                        return params;
                    }

                    var section = mod.split(\'_\')[0];
                    if(mod===\'file_FileRefListListref\') {
                        section = \'web\';
                    }
                    //get id
                    if (top.fsMod.recentIds[section]) {
                        params = \'id=\' + top.fsMod.recentIds[section] + \'&\' + params;
                    }

                    return params;
                };

            });
 }, 3000);
        ',
            false);
    }
}
