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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Add actions of backend layout columns
 */
class BackendLayoutColumnActions implements FormDataProviderInterface
{

    /**
     * Add data
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $tcaConfiguration = $result['processedTca']['ctrl']['EXT']['wireframe']['content_elements'];

        foreach ($result['processedTca']['backendLayout']['columns'] as &$column) {
            if ($column['locked']) {
                continue;
            }

            $column['actions']['prependContentElement'] = BackendUtility::getModuleUrl('record_edit', [
                'edit' => [
                    $tcaConfiguration['table'] => [
                        $result['vanillaUid'] => 'new'
                    ]
                ],
                'defVals' => [
                    $tcaConfiguration['foreign_table'] => [
                        $tcaConfiguration['position_field'] => $column['position'],
                        $GLOBALS['TCA'][$tcaConfiguration['foreign_table']]['ctrl']['languageField'] => $result['languageUid']
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ]);
        }

        return $result;
    }
}
