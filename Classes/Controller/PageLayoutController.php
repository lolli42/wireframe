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
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Wireframe\Form\Data\Group\ContentContainer;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

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
            $translationInfo = $this->translationConfigurationProvider->translationInfo('pages', $page);
            $formData = array_merge([
                'renderType' => 'backendLayoutContainer',
                'pageLayoutView' => GeneralUtility::makeInstance(PageLayoutView::class),
                'languageUid' => $language
            ], $this->compileFormData($language, $translationInfo));

            $formResult = $this->createFormResult($formData);

            $this->view->assignMultiple([
                'formBefore' => $formResult['before'],
                'formAfter' => $formResult['after'],
                'formContent' => $formResult['html'],
                'formAction' => $this->getHref('PageContent', 'index', [
                    'page' => $page,
                    'language' => $language
                ])
            ]);
        } else {
            $this->view->assignMultiple([
                'infoBoxTitle' => 'Title',
                'infoBoxMessage' => 'Message'
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
            $languages = $language > 0 ? [$translationInfo['translations'][$language]['sys_language_uid']] : array_keys($translationInfo['translations']);
            $formData = array_merge([
                'renderType' => 'translationContainer',
                'pageLayoutView' => GeneralUtility::makeInstance(PageLayoutView::class),
                'languageUid' => $language
            ], $this->compileFormData(0, $translationInfo));

            foreach ($languages as $language) {
                $formData['languageOverlays'][$language] = $this->compileFormData($language, $translationInfo);
            }

            $formResult = $this->createFormResult($formData);

            $this->view->assignMultiple([
                'formBefore' => $formResult['before'],
                'formAfter' => $formResult['after'],
                'formContent' => $formResult['html'],
                'formAction' => $this->getHref('PageContent', 'translate', [
                    'page' => $page,
                    'language' => $language
                ])
            ]);
        } else {
            $this->view->assignMultiple([
                'infoBoxTitle' => 'Title',
                'infoBoxMessage' => 'Message'
            ]);
        }
    }

    /**
     * @param int $languageUid
     * @param array $translationInfo
     * @return array
     */
    protected function compileFormData($languageUid, $translationInfo) {
        $formDataGroup = GeneralUtility::makeInstance(ContentContainer::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'tableName' => $languageUid > 0 ? $translationInfo['translation_table'] : $translationInfo['table'],
            'vanillaUid' => $languageUid > 0 && $translationInfo['translations'][$languageUid] ?
                $translationInfo['translations'][$languageUid]['uid'] : $translationInfo['uid'],
            'command' => 'edit',
            'returnUrl' => ''
        ];

        return $formDataCompiler->compile($formDataCompilerInput);
    }

    /**
     * @param $formData
     * @return array
     * @throws \TYPO3\CMS\Backend\Form\Exception
     */
    protected function createFormResult($formData) {
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

        $formResult = $nodeFactory->create($formData)->render();

        $formResultCompiler->mergeResult($formResult);

        $formResult['before'] = $formResultCompiler->JStop();
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
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);

        if ($this->request->hasArgument('page') && (int)$this->request->getArgument('page') > 0) {
            $this->generateMenus((int)$this->request->getArgument('page'));
        }

        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
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
     * Generates the menus
     *
     * @param int $page
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function generateMenus($page)
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

        $translations = $this->translationConfigurationProvider->translationInfo('pages', $page);
        $languages = array_intersect_key(
            $this->translationConfigurationProvider->getSystemLanguages(),
            array_merge(array_flip([0]), $translations['translations'])
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