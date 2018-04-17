<?php
namespace Colorcube\FileRefList\Controller;

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

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Utility\ListUtility;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Colorcube\FileRefList\FileList;

/**
 * Script Class for creating the list of files in the File > FileRefList module
 */
class FileReferencesListController extends ActionController
{
    /**
     * @var string prefix for session
     */
    const SESSION_PREFIX = 'tx_fileref_';

    /**
     * @var array
     */
    public $MOD_MENU = [];

    /**
     * @var array
     */
    public $MOD_SETTINGS = [];

    /**
     * Document template object
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * @var int the current page id
     */
    protected $pageId;

    /**
     * @var int
     */
    protected $returnId;

    /**
     * @var int
     */
    protected $depth;

    /**
     * Number of levels to enable recursive settings for
     *
     * @var int
     */
    protected $getLevels = 10;

    /**
     * @var string
     */
    public $perms_clause;

    /**
     * @var array
     */
    protected $pageRecord = [];

    /**
     * @var bool
     */
    protected $isAccessibleForCurrentUser = false;

    /**
     * @var FlashMessage
     */
    protected $errorMessage;

    /**
     * Pointer to listing
     *
     * @var int
     */
    public $pointer;

    /**
     * "Table"
     * @var string
     */
    public $table;

    /**
     * Thumbnail mode.
     *
     * @var string
     */
    public $imagemode;

    /**
     * @var string
     */
    public $cmd;

    /**
     * Defines behaviour when uploading files with names that already exist; possible values are
     * the values of the \TYPO3\CMS\Core\Resource\DuplicationBehavior enumeration
     *
     * @var \TYPO3\CMS\Core\Resource\DuplicationBehavior
     */
    protected $overwriteExistingFiles;

    /**
     * The file_ref_list object
     *
     * @var FileList
     */
    public $file_ref_list = null;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'file_listref';

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * BackendTemplateView Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;


    /**
     * Initialize variables, file object
     * Incoming GET vars include id, pointer, table, imagemode
     *
     * @throws \RuntimeException
     * @throws Exception\InsufficientFolderAccessPermissionsException
     */
    public function initializeObject()
    {
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_mod_file_list.xlf');
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_misc.xlf');
        $this->getLanguageService()->includeLLFile('EXT:file_ref_list/Resources/Private/Language/locallang_mod_file_ref_list.xlf');

        // Configure the "menu" - which is used internally to save the values of sorting, displayThumbs etc.
        $this->menuConfig();
    }


    /**
     * Initialize action
     */
    protected function initializeAction()
    {
        // determine id parameter
        $this->pageId = (int)GeneralUtility::_GP('id');
        if ($this->request->hasArgument('id')) {
            $this->pageId = (int)$this->request->getArgument('id');
        }

        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        $this->pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->pageId, $this->perms_clause);
        $this->isAccessibleForCurrentUser = ($this->pageId && is_array($this->pageRecord)) || (!$this->pageId && $this->getBackendUser()->isAdmin());


        // determine depth parameter
        $this->depth = ((int)GeneralUtility::_GP('depth') > 0)
            ? (int) GeneralUtility::_GP('depth')
            : $this->getBackendUser()->getSessionData(self::SESSION_PREFIX . 'depth');
        if ($this->request->hasArgument('depth')) {
            $this->depth = (int)$this->request->getArgument('depth');
        }
        $this->getBackendUser()->setAndSaveSessionData(self::SESSION_PREFIX . 'depth', $this->depth);
        $this->lastEdited = GeneralUtility::_GP('lastEdited');
        $this->returnId = GeneralUtility::_GP('returnId');
        $this->pageRecord = BackendUtility::readPageAccess($this->pageId, ' 1=1');


        $this->pointer = GeneralUtility::_GP('pointer');
        $this->table = GeneralUtility::_GP('table');
        $this->imagemode = GeneralUtility::_GP('imagemode');
        $this->cmd = GeneralUtility::_GP('cmd');
        $this->overwriteExistingFiles = DuplicationBehavior::cast(GeneralUtility::_GP('overwriteExistingFiles'));



    }



    /**
     * Setting the menu/session variables
     */
    public function menuConfig()
    {
        // MENU-ITEMS:
        // If array, then it's a selector box menu
        // If empty string it's just a variable, that will be saved.
        // Values NOT in this array will not be saved in the settings-array for the module.
        $this->MOD_MENU = [
            'sort' => '',
            'reverse' => '',
            'displayThumbs' => '',
            'clipBoard' => '',
            'bigControlPanel' => ''
        ];
        // CLEANSE SETTINGS
        $this->MOD_SETTINGS = BackendUtility::getModuleData(
            $this->MOD_MENU,
            GeneralUtility::_GP('SET'),
            $this->moduleName
        );


    }

    /**
     * Initialize the view
     *
     * @param ViewInterface $view The view
     */
    public function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/FileRefList/FileListLocalisation');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->registerDocHeaderButtons();
    }

    /**
     */
    public function initializeIndexAction()
    {
        // Apply predefined values for hidden checkboxes
        // Set predefined value for DisplayBigControlPanel:
        $backendUser = $this->getBackendUser();
        if ($backendUser->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'activated') {
            $this->MOD_SETTINGS['bigControlPanel'] = true;
        } elseif ($backendUser->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'deactivated') {
            $this->MOD_SETTINGS['bigControlPanel'] = false;
        }
        // Set predefined value for DisplayThumbnails:
        if ($backendUser->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'activated') {
            $this->MOD_SETTINGS['displayThumbs'] = true;
        } elseif ($backendUser->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'deactivated') {
            $this->MOD_SETTINGS['displayThumbs'] = false;
        }
        // Set predefined value for Clipboard:
        if ($backendUser->getTSConfigVal('options.file_list.enableClipBoard') === 'activated') {
            $this->MOD_SETTINGS['clipBoard'] = true;
        } elseif ($backendUser->getTSConfigVal('options.file_list.enableClipBoard') === 'deactivated') {
            $this->MOD_SETTINGS['clipBoard'] = false;
        }
        // If user never opened the list module, set the value for displayThumbs
        if (!isset($this->MOD_SETTINGS['displayThumbs'])) {
            $this->MOD_SETTINGS['displayThumbs'] = $backendUser->uc['thumbnailsByDefault'];
        }
        if (!isset($this->MOD_SETTINGS['sort'])) {
            // Set default sorting
            $this->MOD_SETTINGS['sort'] = 'file';
            $this->MOD_SETTINGS['reverse'] = 0;
        }
        if (!$this->pageId) {
            $this->forward('missingFolder');
        }
    }

    /**
     */
    public function indexAction()
    {
        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->setTitle($this->getLanguageService()->getLL('files'));

        // There there was access to this file path, continue, make the list
        if ($this->pageId) {
            // Create fileListing object
            $this->file_ref_list = GeneralUtility::makeInstance(FileList::class, $this);
            $this->file_ref_list->thumbs = $GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] && $this->MOD_SETTINGS['displayThumbs'];
            // Create clipboard object and initialize that
            $this->file_ref_list->clipObj = GeneralUtility::makeInstance(Clipboard::class);
            $this->file_ref_list->clipObj->fileMode = 1;
            $this->file_ref_list->clipObj->initializeClipboard();
            $CB = GeneralUtility::_GET('CB');
            if ($this->cmd === 'setCB') {
                $CB['el'] = $this->file_ref_list->clipObj->cleanUpCBC(array_merge(
                    GeneralUtility::_POST('CBH'),
                    (array)GeneralUtility::_POST('CBC')
                ), '_FILE');
            }
            if (!$this->MOD_SETTINGS['clipBoard']) {
                $CB['setP'] = 'normal';
            }
            $this->file_ref_list->clipObj->setCmd($CB);
            $this->file_ref_list->clipObj->cleanCurrent();
            // Saves
            $this->file_ref_list->clipObj->endClipboard();
            // If the "cmd" was to delete files from the list (clipboard thing), do that:
            if ($this->cmd === 'delete') {
                $items = $this->file_ref_list->clipObj->cleanUpCBC(GeneralUtility::_POST('CBC'), '_FILE', 1);
                if (!empty($items)) {
                    // Make command array:
                    $FILE = [];
                    foreach ($items as $v) {
                        $FILE['delete'][] = ['data' => $v];
                    }
                    // Init file processing object for deleting and pass the cmd array.
                    /** @var ExtendedFileUtility $fileProcessor */
                    $fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
                    $fileProcessor->setActionPermissions();
                    $fileProcessor->setExistingFilesConflictMode($this->overwriteExistingFiles);
                    $fileProcessor->start($FILE);
                    $fileProcessor->processData();
                }
            }
            // Start up file_ref_listing object, include settings.
            $this->pointer = MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
            $this->file_ref_list->start(
                $this->pageId,
                $this->pointer,
                $this->MOD_SETTINGS['sort'],
                $this->MOD_SETTINGS['reverse'],
                $this->MOD_SETTINGS['clipBoard'],
                $this->MOD_SETTINGS['bigControlPanel']
            );
            // Generate the list
            $this->file_ref_list->generateList();
            // Set top JavaScript:
            $this->view->getModuleTemplate()->addJavaScriptCode(
                'FileListIndex',
                'if (top.fsMod) top.fsMod.recentIds["web"] = "' . rawurlencode($this->pageId) . '";' . $this->file_ref_list->CBfunctions() . '
                function jumpToUrl(URL) {
                    window.location.href = URL;
                    return false;
                };
                
                '
            );
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/FileRefList/FileDelete');
            $pageRenderer->addInlineLanguageLabelFile('EXT:lang/Resources/Private/Language/locallang_alt_doc.xlf', 'buttons');



            // Setting up the buttons
            $this->registerButtons();

//            $pageRecord = [
//                '_additional_info' => $this->file_ref_list->getFolderInfo(),
//                'combined_identifier' => $this->pageId,
//            ];
//            $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($pageRecord);
            if ($this->isAccessibleForCurrentUser) {
                $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
            }

            $this->view->assign('headline', $this->pageRecord['title'] . ' ['.$this->pageId.']');
            $this->view->assign('listHtml', $this->file_ref_list->HTMLcode);

            $this->view->assign('checkboxes', [
                'bigControlPanel' => [
                    'enabled' => $this->getBackendUser()->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'selectable',
                    'label' => htmlspecialchars($this->getLanguageService()->getLL('bigControlPanel')),
                    'html' => BackendUtility::getFuncCheck(
                        $this->pageId,
                        'SET[bigControlPanel]',
                        $this->MOD_SETTINGS['bigControlPanel'],
                        '',
                        '',
                        'id="bigControlPanel"'
                    ),
                ],
                'displayThumbs' => [
                    'enabled' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] && $this->getBackendUser()->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'selectable',
                    'label' => htmlspecialchars($this->getLanguageService()->getLL('displayThumbs')),
                    'html' => BackendUtility::getFuncCheck(
                        $this->pageId,
                        'SET[displayThumbs]',
                        $this->MOD_SETTINGS['displayThumbs'],
                        '',
                        '',
                        'id="checkDisplayThumbs"'
                    ),
                ],
                'enableClipBoard' => [
                    'enabled' => $this->getBackendUser()->getTSConfigVal('options.file_list.enableClipBoard') === 'selectable',
                    'label' => htmlspecialchars($this->getLanguageService()->getLL('clipBoard')),
                    'html' => BackendUtility::getFuncCheck(
                        $this->pageId,
                        'SET[clipBoard]',
                        $this->MOD_SETTINGS['clipBoard'],
                        '',
                        '',
                        'id="checkClipBoard"'
                    ),
                ]
            ]);
            $this->view->assign('showClipBoard', (bool)$this->MOD_SETTINGS['clipBoard']);
            $this->view->assign('clipBoardHtml', $this->file_ref_list->clipObj->printClipboard());
            $this->view->assign('folderIdentifier', $this->pageId);
            $this->view->assign('fileDenyPattern', $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']);
            $this->view->assign('maxFileSize', GeneralUtility::getMaxUploadFileSize() * 1024);
        } else {
            $this->forward('missingFolder');
        }
    }

    /**
     */
    public function missingFolderAction()
    {
//        $this->addFlashMessage("message", 'title',
//            FlashMessage::INFO, TRUE);
    }



    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocHeaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        // CSH
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('file_ref_list_module');
        $buttonBar->addButton($cshButton);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return void
     */
    protected function registerButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        /** @var IconFactory $iconFactory */
        $iconFactory = $this->view->getModuleTemplate()->getIconFactory();

        /** @var $resourceFactory ResourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $lang = $this->getLanguageService();

        // Refresh page
        $refreshLink = GeneralUtility::linkThisScript(
            [
                'target' => rawurlencode($this->pageId),
                'imagemode' => $this->file_ref_list->thumbs
            ]
        );
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref($refreshLink)
            ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);


        // Shortcut
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $shortCutButton = $buttonBar->makeShortcutButton()->setModuleName('file_FileRefListList');
            $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }

    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
