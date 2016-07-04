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

/**
 * Add TCA configuration for the relationship between the content container and its content elements
 */
class Tca implements FormDataProviderInterface
{

    /**
     * Add data
     *
     * @param array $result
     * @return array
     * @todo Add own key like `contentColumnName` additionally instead of using first element of `columnsToProcess`
     */
    public function addData(array $result)
    {
        $columnToProcess = $result['columnsToProcess'][0];
        $tcaConfiguration = $result['processedTca']['columns'][$columnToProcess]['config'];

        if (empty($tcaConfiguration) || !is_array($tcaConfiguration)) {
            throw new \InvalidArgumentException(
                'Missing column ' . $columnToProcess . ' in TCA for table ' . $result['tableName'],
                1465680013
            );
        }

        if (empty($tcaConfiguration['position_field'])) {
            throw new \InvalidArgumentException(
                'Missing position field in TCA for column ' . $columnToProcess . ' of table ' . $result['tableName'],
                1465680589
            );
        }

        if (empty($GLOBALS['TCA'][$tcaConfiguration['foreign_table']]['ctrl']['sortby'])) {
            throw new \InvalidArgumentException(
                'Missing sorting field in TCA for table ' . $result['tableName'],
                1465681034
            );
        }

        // @todo Maybe another name or another place
        $tcaConfiguration['column_name'] = $columnToProcess;

        $result['processedTca']['contentElementTca'] = $GLOBALS['TCA'][$tcaConfiguration['foreign_table']];
        $result['processedTca']['contentContainerConfig'] = $tcaConfiguration;

        return $result;
    }
}
