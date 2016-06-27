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
 * Add the language of a content element
 */
class Language implements FormDataProviderInterface
{

    /**
     * Add data
     *
     * @param array $result
     * @return array
     * @todo Having `languageUid` as top level key
     * @todo Language field might no be in all cases a single select
     */
    public function addData(array $result)
    {
        if (!empty($result['processedTca']['ctrl']['languageField'])) {
            $field = $result['processedTca']['ctrl']['languageField'];

            $result['processedTca']['languageUid'] = (int)$result['databaseRow'][$field][0];
        } else {
            $result['processedTca']['languageUid'] = -1;
        }

        return $result;
    }
}
