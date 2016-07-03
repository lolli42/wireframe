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
        $tcaConfiguration = $result['processedTca']['columns'][$columnName]['config'];
        
        foreach ($result['processedTca']['columns'][$columnName]['children'] as $key => &$child) {
            // @todo Having an array here is not always the case 
            $position = $child['databaseRow'][$tcaConfiguration['position_field']][0];
            $result['processedTca']['contentElementPositions'][$position][] = &$child;
        }

        return $result;
    }
}
