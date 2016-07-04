<?php
namespace TYPO3\CMS\Wireframe\Form\Element\ContentElement;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Wireframe\Form\Element\AbstractElement;

/**
 * Generation of preview of a content elementGenerate
 */
class WizardItemElement extends AbstractElement
{
    /**
     * Render the preview
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $content = null;
        $view = GeneralUtility::makeInstance(StandaloneView::class);

        $view->setTemplatePathAndFilename($this->getTemplatePathAndFilename());
        $view->assignMultiple([
            'element' => $this->data['definition']
        ]);

        return [
            'html' => $view->render()
        ];
    }

    /**
     * @return string
     */
    protected function getTemplatePathAndFilename()
    {
        return GeneralUtility::getFileAbsFileName(
            'EXT:wireframe/Resources/Private/Templates/Form/Element/ContentElement/WizardItem.html'
        );
    }
}
