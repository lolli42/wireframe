<?php
namespace TYPO3\CMS\Wireframe\Form\Container\ContentElement;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Wireframe\Form\Container\AbstractContainer;

/**
 * Render a wizard form with content element definitions and positions
 *
 * This is an entry container called from controllers.
 */
class WizardContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($this->getTemplatePathAndFilename());

        $view->assignMultiple([
            'definitions' => $this->renderDefinitions(),
            'positions' => $this->renderPositions(),
            'tca' => $this->data['processedTca']['contentContainerConfig']
        ]);

        return array_merge($this->initializeResultArray(), [
            'requireJsModules' => [
                'TYPO3/CMS/Backend/Tabs',
                'TYPO3/CMS/Backend/ClickMenu',
                'TYPO3/CMS/Wireframe/Wizard'
            ],
            'stylesheetFiles' => [
                ExtensionManagementUtility::extRelPath('wireframe') . 'Resources/Public/Css/Wizard.css'
            ],
            'html' => $view->render()
        ]);
    }

    protected function renderDefinitions() {
        $tabs = [];

        foreach ($this->data['processedTca']['contentElementDefinitions'] as $group) {
            $content = '';

            foreach ($group['elements'] as $element) {
                $formResult = $this->nodeFactory->create([
                    'renderType' => 'contentElementWizardItem',
                    'definition' => $element
                ])->render();

                $content .= $formResult['html'];
            }

            $tabs[] = [
                'label' => $group['header'],
                'content' => $content
            ];
        }

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/DocumentTemplate/Tabs.html'));
        $view->assignMultiple(array(
            'id' => 'DTM-' . GeneralUtility::shortMD5('content-element-wizard'),
            'items' => $tabs,
            'defaultTabIndex' => 1,
            'wrapContent' => true,
            'storeLastActiveTab' => true,
        ));

        return $view->render();
    }

    protected function renderPositions() {
        if ($this->data['processedTca']['backendLayout']) {
            $formResult = $this->nodeFactory->create(array_merge($this->data, [
                'renderType' => 'backendLayoutPositionContainer'
            ]))->render();

            return $formResult['html'];
        }

        return null;
    }

    protected function getTemplatePathAndFilename() {
        return GeneralUtility::getFileAbsFileName(
            'EXT:wireframe/Resources/Private/Templates/Form/Container/ContentElement/Wizard.html'
        );
    }
}
