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
 * Add translation information about a content element
 */
class Translation implements FormDataProviderInterface
{
    /**
     * Add data
     *
     * @param array $result
     * @return array
     * @todo Having `hasTranslations` as top level key
     * @todo Implement also for records in default language
     */
    public function addData(array $result)
    {
        if ($result['processedTca']['languageUid'] > 0) {
            $result['processedTca']['hasTranslations'] = $result['defaultLanguageRow'] !== null;
        }

        return $result;
    }
}
