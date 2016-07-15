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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Add Fluid preview template file name for a content element from PageTsConfig
 */
class FluidPreviewTemplate implements FormDataProviderInterface
{
    /**
     * Add data
     *
     * @param array $result
     * @return array
     * @todo Having `fluidPreviewTemplateFilename` as top level key
     */
    public function addData(array $result)
    {
        $typeField = $result['processedTca']['ctrl']['type'];
        $typeValue = empty($result['databaseRow'][$typeField]) ? 'default' : $result['databaseRow'][$typeField];

        // @todo I don't get it! When is it an array in `databaseRow` and when it's not? And what if there are multiple values?
        $type = is_array($typeValue) ? $typeValue[0] : $typeValue;

        if ($result['inlineParentTableName'] === 'pages') {
            $tsConfig = BackendUtility::getModTSconfig(
                $result['databaseRow']['pid'],
                'mod.web_layout.' . $result['tableName'] . '.preview.' . $type
            );
        } else {
            $tsConfig = BackendUtility::getModTSconfig(
                $result['databaseRow']['pid'],
                'TCEFORM.' . $result['inlineParentTableName'] . '.' . $result['inlineParentFieldName'] . '.contentElementPreview.' . $type
            );
        }

        if (!empty($tsConfig['value'])) {
            $result['processedTca']['fluidPreviewTemplateFilename'] = $tsConfig['value'];
        }

        return $result;
    }
}
