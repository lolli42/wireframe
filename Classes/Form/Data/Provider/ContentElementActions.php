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
 * Add actions for the content elements of a content container
 */
class ContentElementActions implements FormDataProviderInterface
{

    /**
     * Add content element actions
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        // @todo PageTsConfig
        foreach ($result['processedTca']['contentElements'] as $position => &$elements) {
            foreach ($elements as &$element) {
                $tcaConfiguration = &$GLOBALS['TCA'][$element['tableName']];

                $element['actions']['insertAfter'] = BackendUtility::getModuleUrl('record_edit', [
                    'edit' => [
                        $result['tableName'] => [
                            -$result['vanillaUid'] => 'new'
                        ]
                    ],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ]);

                if (
                    $this->getBackendUserAuthentication()->recordEditAccessInternals($element['tableName'], $element['databaseRow']) &&
                    $this->getBackendUserAuthentication()->doesUserHaveAccess($element['parentPageRow'], Permission::CONTENT_EDIT)
                ) {
                    $element['actions']['edit'] = BackendUtility::getModuleUrl('record_edit', [
                        'edit' => [
                            $element['tableName'] => [
                                $element['vanillaUid'] => 'edit'
                            ]
                        ],
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    ]) . '#element-' . $element['tableName'] . '-' . $element['vanillaUid'];

                    $element['actions']['delete'] = BackendUtility::getLinkToDataHandlerAction(
                        '&cmd[' . $element['tableName'] . '][' . $element['vanillaUid'] . '][delete]=1'
                    );

                    if (!empty($tcaConfiguration['ctrl']['enablecolumns']['disabled'])) {
                        $field = $tcaConfiguration['ctrl']['enablecolumns']['disabled'];

                        if (
                            $field && $tcaConfiguration['columns'][$field] &&
                            !$tcaConfiguration['columns'][$field]['exclude'] ||
                            $this->getBackendUserAuthentication()->check(
                                'non_exclude_fields',
                                $element['tableName'] . ':' . $field
                            )
                        ) {
                            $flag = (bool)$element['databaseRow'][$field];
                            $element['actions'][$flag ? 'unhide' : 'hide'] = BackendUtility::getLinkToDataHandlerAction(
                                '&data[' . $element['tableName'] . '][' . $element['vanillaUid'] . '][' . $field . ']=' . (int)!$flag
                            );
                        }
                    }
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
