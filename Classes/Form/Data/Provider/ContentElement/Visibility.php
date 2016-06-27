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
 * Add visibility information for each content element of a content container
 */
class Visibility implements FormDataProviderInterface
{

    /**
     * Add data
     *
     * @param array $result
     * @return array
     * @todo Having `visible` as top level key
     */
    public function addData(array $result)
    {
        if (!empty($result['processedTca']['ctrl']['enablecolumns']['disabled'])) {
            $field = $result['processedTca']['ctrl']['enablecolumns']['disabled'];

            if ($field && $result['databaseRow'][$field]) {
                $flag = (bool)$result['databaseRow'][$field];
                $result['processedTca']['visible'] = $flag;
            }
        }

        return $result;
    }
}
