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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Add accessibility information about backend layout columns.
 */
class BackendLayoutColumnAccessibility implements FormDataProviderInterface
{

    /**
     * Add data
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $permissions = $this->getBackendUserAuthentication()->calcPerms(
            $result['tableName'] === 'pages' ? $result['databaseRow'] : $result['parentPageRow']
        );

        foreach ($result['processedTca']['backendLayout']['columns'] as &$column) {
            $column['assigned'] = is_numeric($column['position']);
            $column['locked'] = !$this->getBackendUserAuthentication()->isAdmin() &&
                (($permissions & Permission::CONTENT_EDIT) !== Permission::CONTENT_EDIT ||
                    isset($result['databaseRow']['editlock']) && $result['databaseRow']['editlock']);

            if ($column['assigned']) {
                $result['processedTca']['backendLayout']['positions'][] = (int)$column['position'];
            }
        }

        $result['processedTca']['backendLayout']['positions'] =
            array_unique((array)$result['processedTca']['backendLayout']['positions']);

        if (!empty($result['pageTsConfig']['mod']['SHARED']['properties']['colPos_list'])) {
            $result['processedTca']['backendLayout']['positions'] = array_unique(array_intersect(
                $result['processedTca']['backendLayout']['positions'],
                GeneralUtility::intExplode(
                    ',',
                    trim($result['pageTsConfig']['mod']['SHARED']['properties']['colPos_list'])
                )
            ));
        }

        foreach ($result['processedTca']['backendLayout']['columns'] as &$column) {
            // @todo originally BackendUtility::getProcessedValue() is involved to check this
            $column['restricted'] = array_search($column['colPos'], $result['processedTca']['backendLayout']['positions']) === false;
        }

        return $result;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
