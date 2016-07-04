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
 * Segment all content elements by their backend layout position
 */
class Positions implements FormDataProviderInterface
{
    /**
     * Add data
     *
     * @param array $result
     * @return array
     * @todo Having `contentElementPositions` as top level key
     */
    public function addData(array $result)
    {
        $columnName = $result['processedTca']['contentContainerConfig']['column_name'];
        $positionField = $result['processedTca']['contentContainerConfig']['position_field'];
        
        foreach ($result['processedTca']['columns'][$columnName]['children'] as $key => &$child) {
            $positionValue = $child['databaseRow'][$positionField];
            // @todo I don't get it! When is it an array in `databaseRow` and when it's not? And what if there are multiple values?
            $position = is_array($positionValue) ? $positionValue[0] : $positionValue;
            $result['processedTca']['contentElementPositions'][$position][] = &$child;
        }

        return $result;
    }
}
