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

        $this->getBackendUserAuthentication()->setAndSaveSessionData(self::class, $sessionData);
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
            $this->generateMenu((int)$this->request->getArgument('page'));
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
     * Generates the action menu
     *
     * @param int $page
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function generateMenu($page)
    {
        $translations = $this->translationConfigurationProvider->translationInfo('pages', $page);
        $languages = array_intersect_key(
            $this->translationConfigurationProvider->getSystemLanguages(),
            array_merge(array_flip([0]), $translations['translations'])
        );

        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('languageMenu');
        // $menu->setLabel($this->getLanguageService()->sL('LLL:EXT:lang/locallang_general.xlf:LGL.language'));

        foreach ($languages as $language) {
            $menu->addMenuItem(
                $menu->makeMenuItem()
                    ->setTitle($language['title'])
                    ->setHref($this->getHref('PageLayout', 'index', [
                        'page' => $page,
                        'language' => $language['uid']
                    ]))
                    ->setActive((int)$this->request->getArgument('language') === $language['uid'])
            );
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

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
            $translations = $this->translationConfigurationProvider->translationInfo('pages', $page);
            $formDataGroup = GeneralUtility::makeInstance(ContentContainer::class);
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
            $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
            $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

            $formDataCompilerInput = [
                'tableName' => $language > 0 ? $translations['translation_table'] : $translations['table'],
                'vanillaUid' => $language > 0 && $translations['translations'][$language] ?
                    $translations['translations'][$language]['uid'] : $translations['uid'],
                'command' => 'edit',
                'returnUrl' => ''
            ];

            $formData = $formDataCompiler->compile($formDataCompilerInput);
            
            $formData['renderType'] = 'backendLayoutContainer';
            $formData['languageUid'] = $language;
            // only for `PageLayoutViewDrawItemHookInterface`
            $formData['pageLayoutView'] = GeneralUtility::makeInstance(PageLayoutView::class);

            $formResult = $nodeFactory->create($formData)->render();

            $formResultCompiler->mergeResult($formResult);

            $this->view->assignMultiple([
                'formBefore' => $formResultCompiler->JStop(),
                'formAfter' => $formResultCompiler->printNeededJSFunctions(),
                'formContent' => $formResult['html'],
                'formAction' => $this->getHref('PageContent', 'index', [
                    'page' => $page,
                    'language' => $language['uid']
                ])
            ]);
        } else {
            $this->view->assignMultiple([
                'infoBoxTitle' => 'Title',
                'infoBoxMessage' => 'Message'
            ]);
        }
    }
}