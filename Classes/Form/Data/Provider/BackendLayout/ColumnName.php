<?php
namespace TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayout;

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
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Add names of backend layout columns
 */
class ColumnName implements FormDataProviderInterface
{

    /**
     * Add data
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);

        $this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');

        foreach ($result['processedTca']['backendLayout']['columns'] as &$column) {
            $column['name'] = GeneralUtility::isFirstPartOfStr($column['name'], 'LLL:') ?
                $this->getLanguageService()->sL($column['name']) : $column['name'];
        }

        foreach ($result['processedTca']['backendLayout']['columns'] as &$column) {
            $name = BackendUtility::getProcessedValue(
                $result['processedTca']['contentContainerConfig']['foreign_table'],
                $result['processedTca']['contentContainerConfig']['position_column'],
                $column['colPos']
            );

            if ($result['tableName'] === 'pages') {
                $tcaItems = $backendLayoutView->getColPosListItemsParsed(
                    $result['tableName'] === 'pages' ? $result['databaseRow']['uid'] : $result['databaseRow']['pid']
                );
                foreach ($tcaItems as $tcaItem) {
                    if ($tcaItem[1] == $column['position']) {
                        $name = $this->getLanguageService()->sL($tcaItem[0]);
                    }
                }
            }

            if ($name) {
                $column['name'] = $name;
            }
        }

        return $result;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
