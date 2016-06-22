<?php
namespace TYPO3\CMS\Wireframe\Form\Data\Provider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Add Fluid preview template file name for the content elements of a content container
 */
class ContentElementPreviewTemplate implements FormDataProviderInterface
{

    /**
     * Add content element template
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['contentElements'] as $position => &$elements) {
            foreach ($elements as &$element) {
                // @todo TsConfig path `mod.web_layout` doesn't fit for all use cases
                $tsConfig = BackendUtility::getModTSconfig(
                    $element['databaseRow']['pid'], 'mod.web_layout.' . $element['tableName'] . '.preview'
                );

                $type = empty($element['databaseRow'][$element['processedTca']['ctrl']['type']]) ? 'default' :
                    $element['databaseRow'][$element['processedTca']['ctrl']['type']];

                if (!empty($tsConfig['properties'][$type])) {
                    $element['previewTemplateFilename'] = $tsConfig['properties'][$type];
                }
            }
        }

        return $result;
    }
}
