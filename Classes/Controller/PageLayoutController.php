<?php
namespace TYPO3\CMS\Wireframe\Controller;

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

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Wireframe\Form\Data\Group\ContentContainer;
use TYPO3\CMS\Wireframe\Form\Data\Group\ContentElement\Definitions;

/**
 * Controller for Web > Page module
 */
class PageLayoutController extends ActionController
{

    /**
     * @var TranslationConfigurationProvider
     */
    protected $translationConfigurationProvider;

    /**
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->translationConfigurationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
    }

    /**
     * Index action
     *
     * @param int $page
     * @param int $language
     * @return void
     */
    public function indexAction($page, $language = 0)
    {
        if ($page > 0) {
            $formData = array_merge([
                'renderType' => 'backendLayoutContainer',
                'pageLayoutView' => GeneralUtility::makeInstance(PageLayoutView::class),
                'languageUid' => $language,
                'displayLegacyActions' => true
            ], $this->compileFormData($page));

            $formResult = $this->createFormResult($formData);

            $this->view->assignMultiple([
                'title' => $formData['recordTitle'],
                'form' => [
                    'before' => $formResult['before'],
                    'after' => $formResult['after'],
                    'content' => $formResult['html'],
                    'action' => $this->getHref('PageContent', 'index', [
                        'page' => $page,
                        'language' => $language
                    ])
                ]
            ]);
        } else {
            $this->view->assignMultiple([
                'infoBox' => [
                    'title' => 'Help',
                    'message' => '...'
                ]
            ]);
        }
    }

    /**
     * Translate action
     *
     * @param int $page
     * @param int $language
     * @return void
     */
    public function translateAction($page, $language = 0)
    {
        if ($page > 0) {
            $translationInfo = $this->translationConfigurationProvider->translationInfo('pages', $page);
            $languages = $language > 0 ? [$language] : array_keys($translationInfo['translations']);
            $formData = array_merge([
                'renderType' => 'translationContainer',
                'pageLayoutView' => GeneralUtility::makeInstance(PageLayoutView::class),
                'languageUids' => array_merge([0], $languages),
                'displayLegacyActions' => true
            ], $this->compileFormData($page));

            $formResult = $this->createFormResult($formData);

            $this->view->assignMultiple([
                'title' => $formData['recordTitle'],
                'form' => [
                    'before' => $formResult['before'],
                    'after' => $formResult['after'],
                    'content' => $formResult['html'],
                    'action' => $this->getHref('PageContent', 'translate', [
                        'page' => $page,
                        'language' => $language
                    ])
                ]
            ]);
        } else {
            $this->view->assignMultiple([
                'infoBox' => [
                    'title' => 'Help',
                    'message' => '...'
                ]
            ]);
        }
    }

    /**
     * @param int $page
     * @param string $formDataGroupClass
     * @return array
     */
    protected function compileFormData($page, $formDataGroupClass = ContentContainer::class)
    {
        $formDataGroup = GeneralUtility::makeInstance($formDataGroupClass);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'tableName' => 'pages',
            'vanillaUid' => $page,
            'command' => 'edit',
            'returnUrl' => $this->getHref(null, null, []),
            'columnsToProcess' => ['content']
        ];

        return $formDataCompiler->compile($formDataCompilerInput);
    }

    /**
     * @param $formData
     * @return array
     * @throws \TYPO3\CMS\Backend\Form\Exception
     */
    protected function createFormResult($formData)
    {
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

        $formResult = $nodeFactory->create($formData)->render();

        $formResultCompiler->mergeResult($formResult);

        // @todo The API says `JavaScript code added BEFORE the form is drawn` but in fact it renders the CSS
        $this->view->getModuleTemplate()->getPageRenderer()->addHeaderData($formResultCompiler->JStop());
        $formResult['after'] = $formResultCompiler->printNeededJSFunctions();

        return $formResult;
    }

    /**
     * Initializes the arguments
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function initializeAction()
    {
        $sessionData = $this->getBackendUserAuthentication()->getSessionData(self::class);

        if (!$this->request->hasArgument('language')) {
            $this->request->setArgument('language', (int)$sessionData['language']);
        } else {
            $sessionData['language'] = $this->request->getArgument('language');
        }

        if (!$this->request->hasArgument('page')) {
            $this->request->setArgument('page', (int)GeneralUtility::_GP('id'));
        }

        if ($this->request->hasArgument('action')) {
            $sessionData['action'] = $this->request->getArgument('action');
        }

        $this->getBackendUserAuthentication()->setAndSaveSessionData(self::class, $sessionData);

        if ($sessionData['action'] && $sessionData['action'] !== $this->request->getControllerActionName()) {
            $this->forward($sessionData['action']);
        }
    }

    /**
     * Set up the view
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);

        // @todo This is nasty! There must be a better way to append the sidebar markup!
        $this->view->getModuleTemplate()->getView()->setLayoutRootPaths(['EXT:wireframe/Resources/Private/Layouts']);
        $this->view->getModuleTemplate()->getView()->setTemplateRootPaths(['EXT:wireframe/Resources/Private/Templates']);

        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        if ($this->request->hasArgument('page') && (int)$this->request->getArgument('page') > 0) {
            $this->createMenus((int)$this->request->getArgument('page'));
            $this->createSidebar((int)$this->request->getArgument('page'));

            // @todo Check access rights
            // @todo Language overlay id
            $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/PageActions', 'function(PageActions) {
                PageActions.setPageId(' . (int)$this->request->getArgument('page') . ');
                PageActions.setLanguageOverlayId(0);
                PageActions.initializePageTitleRenaming();
            }');
        }
    }

    /**
     * Returns the language service
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current backend user
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Creates the URI for a backend action
     *
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @return string
     */
    protected function getHref($controller, $action, $parameters = [])
    {
        return $this->objectManager->get(UriBuilder::class)
            ->setRequest($this->request)
            ->reset()
            ->uriFor($action, $parameters, $controller);
    }

    /**
     * Generates the sidebar
     *
     * @param int $page
     */
    protected function createSidebar($page)
    {
        $formData = $this->compileFormData($page, Definitions::class);
        $this->view->getModuleTemplate()->getView()->assign(
            'sidebar',
            $this->createFormResult(array_merge(
                ['renderType' => 'contentElementSidebarContainer'],
                $formData
            ))
        );
    }

    /**
     * Generates the menus
     *
     * @param int $page
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function createMenus($page)
    {
        $request = $this->getControllerContext()->getRequest();
        $actions = [
            'Columns' => 'index',
            'Languages' => 'translate'
        ];

        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('actionMenu');

        foreach ($actions as $label => $action) {
            $menu->addMenuItem(
                $menu->makeMenuItem()
                    ->setTitle($label)
                    ->setHref(
                        $this->getHref('PageLayout', $action, ['page' => $page, 'action' => $action])
                    )
                    ->setActive(
                        $request->getControllerActionName() === $action
                    )
            );
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);

        $translationInfo = $this->translationConfigurationProvider->translationInfo('pages', $page);
        $languages = $this->translationConfigurationProvider->getSystemLanguages($page);

        uasort($languages, function ($a, $b) {
            return $a['title'] <=> $b['title'];
        });

        $languages = [$languages[0]] + array_intersect_key(
            $languages,
            $translationInfo['translations']
        );

        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('languageMenu');

        foreach ($languages as $language) {
            $menu->addMenuItem(
                $menu->makeMenuItem()
                    ->setTitle($language['title'])
                    ->setHref($this->getHref('PageLayout', $request->getControllerActionName(), [
                        'page' => $page,
                        'language' => $language['uid']
                    ]))
                    ->setActive((int)$this->request->getArgument('language') === $language['uid'])
            );
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }
}
