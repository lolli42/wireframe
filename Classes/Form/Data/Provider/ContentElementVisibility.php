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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Add visibility information for the content elements
 */
class ContentElementVisibility implements FormDataProviderInterface
{

    /**
     * Add content element visibility
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['contentElements'] as $position => &$elements) {
            foreach ($elements as &$element) {
                $tcaConfiguration = &$GLOBALS['TCA'][$element['tableName']];


                if (!empty($tcaConfiguration['ctrl']['enablecolumns']['disabled'])) {
                    $field = $tcaConfiguration['ctrl']['enablecolumns']['disabled'];

                    if ($field && $tcaConfiguration['columns'][$field]) {
                        $flag = (bool)$element['databaseRow'][$field];
                        $element['visible'] = $flag;
                    }
                }
            }
        }

        return $result;
    }
}
