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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Wireframe\Form\Data\Group\ContentContainer;
use TYPO3\CMS\Wireframe\Form\Data\Group\ContentElement\Definitions;

/**
 * Controller for content element wizard
 *
 * Hint: This is the "new content element wizard" that kicks in after clicking
 * "+ cotent" in page module.
 *
 * Imho: rename this module - and further fate of this module should be discussed anyway
 * iirc, for page module, it was planed, to:
 * ** click "+ content"
 * ** sidebar wizard folds out
 * ** click an element
 * In this case, this controller here could vanish
 */
class ContentElementController extends AbstractModule
{

    /**
     * Creates a new content element
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Backend\Form\Exception
     */
    public function createAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        // @todo How to document and validate the parameters when it's wrapped in a server request? Maybe a better process request in `AbstractModule`?
        $parameters = $request->getQueryParams();

        $formDataGroup = GeneralUtility::makeInstance(isset($parameters['columnPosition']) ? Definitions::class : ContentContainer::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'tableName' => $parameters['containerTable'] ? (string)$parameters['containerTable'] : 'pages',
            'vanillaUid' => (int)$parameters['containerUid'],
            'command' => 'edit',
            'returnUrl' => GeneralUtility::sanitizeLocalUrl($parameters['returnUrl']),
            'columnsToProcess' => [$parameters['containerField'] ? (string)$parameters['containerField'] : 'content']
        ];

        // we should think about this - the reason to do this array merge here is that the FormDataCompiler
        // currently does not allow to add new keys to the main array. This restriction could be lifted,
        // artus has ideas about that already
        $formData = array_merge(
            [
                'languageUid' => $parameters['languageUid'] ? (int)$parameters['languageUid'] : 0
            ],
            $formDataCompiler->compile($formDataCompilerInput)
        );
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $formResult = GeneralUtility::makeInstance(NodeFactory::class)->create(array_merge(
            ['renderType' => 'contentElementWizardContainer'],
            $formData
        ))->render();
        $formResultCompiler->mergeResult($formResult);

        if ($parameters['returnUrl']) {
            $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
            $buttonBar->addButton(
                $buttonBar->makeLinkButton()
                    ->setHref(GeneralUtility::sanitizeLocalUrl($parameters['returnUrl']))
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
            );
        }

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:wireframe/Resources/Private/Templates/ContentElement/Create.html');

        $processedTca = $formData['processedTca'];

        $view->assignMultiple([
            'form' => [
                'content' => $formResult['html'],
                'after' => $formResultCompiler->printNeededJSFunctions(),
                'action' => BackendUtility::getModuleUrl(
                    'record_edit',
                    [
                        'edit' => [
                            $processedTca['contentContainerConfig']['foreign_table'] => isset($parameters['columnPosition']) ? [
                                isset($parameters['ancestorUid']) ? '-' . (int)$parameters['ancestorUid'] : (int)$parameters['containerUid'] => 'new'
                            ] : null
                        ],
                        'defVals' => [
                            $processedTca['contentContainerConfig']['foreign_table'] => [
                                $processedTca['contentContainerConfig']['position_field'] => isset($parameters['columnPosition']) ? (int)$parameters['columnPosition'] : null,
                                $processedTca['contentElementTca']['ctrl']['languageField'] => (int)$parameters['languageUid']
                            ]
                        ],
                        'returnUrl' => $formData['returnUrl']
                    ]
                )
            ]
        ]);

        $this->moduleTemplate->getPageRenderer()->addHeaderData($formResultCompiler->JStop());
        $this->moduleTemplate->setContent($view->render());

        $response->getBody()->write($this->moduleTemplate->renderContent());

        return $response;
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
}
