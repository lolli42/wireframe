<?php
namespace TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement;

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

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Wireframe\Form\Data\Group\ContentElement;

/**
 * Post process all inline content elements
 */
class Inline implements FormDataProviderInterface
{
    /**
     * Add data
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $columnName = $result['processedTca']['contentContainerConfig']['column_name'];
        $formDataGroup = GeneralUtility::makeInstance(ContentElement::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

        foreach ($result['processedTca']['columns'][$columnName]['children'] as $key => &$child) {
            $result['processedTca']['columns'][$columnName]['children'][$key] = array_merge(
                $child,
                $formDataCompiler->compile($child)
            );
        }

        return $result;
    }
}
