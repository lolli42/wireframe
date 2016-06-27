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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Add action URLs for the content element
 */
class Actions implements FormDataProviderInterface
{

    /**
     * Add data
     *
     * @param array $result
     * @return array
     * @todo Having `actions` as top level key
     */
    public function addData(array $result)
    {
        // @todo PageTsConfig
        $tcaConfiguration = $result['processedTca'];

        $result['processedTca']['actions']['insertAfter'] = BackendUtility::getModuleUrl('record_edit', [
            'edit' => [
                $result['tableName'] => [
                    -$result['vanillaUid'] => 'new'
                ]
            ],
            'returnUrl' => $result['returnUrl']
        ]);

        if (
            $this->getBackendUserAuthentication()->recordEditAccessInternals($result['tableName'],
                $result['databaseRow']) &&
            $this->getBackendUserAuthentication()->doesUserHaveAccess($result['parentPageRow'],
                Permission::CONTENT_EDIT)
        ) {
            $result['processedTca']['actions']['edit'] = BackendUtility::getModuleUrl('record_edit', [
                    'edit' => [
                        $result['tableName'] => [
                            $result['vanillaUid'] => 'edit'
                        ]
                    ],
                    'returnUrl' => $result['returnUrl']
                ]) . '#element-' . $result['tableName'] . '-' . $result['vanillaUid'];

            $result['processedTca']['actions']['delete'] = BackendUtility::getLinkToDataHandlerAction(
                '&cmd[' . $result['tableName'] . '][' . $result['vanillaUid'] . '][delete]=1'
            );

            if (!empty($tcaConfiguration['ctrl']['enablecolumns']['disabled'])) {
                $field = $tcaConfiguration['ctrl']['enablecolumns']['disabled'];

                if (
                    $field && $tcaConfiguration['columns'][$field] &&
                    !$tcaConfiguration['columns'][$field]['exclude'] ||
                    $this->getBackendUserAuthentication()->check(
                        'non_exclude_fields',
                        $result['tableName'] . ':' . $field
                    )
                ) {
                    $flag = (bool)$result['databaseRow'][$field];
                    $result['processedTca']['actions'][$flag ? 'unhide' : 'hide'] = BackendUtility::getLinkToDataHandlerAction(
                        '&data[' . $result['tableName'] . '][' . $result['vanillaUid'] . '][' . $field . ']=' . (int)!$flag
                    );
                }
            }
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
