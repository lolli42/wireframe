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
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormDataCompiler;

/**
 * Add all content element records of a content container segmented by their position
 */
class ContentElementRecords implements FormDataProviderInterface
{

    /**
     * Add content element records
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $tcaConfiguration = $result['processedTca']['ctrl']['EXT']['wireframe']['content_elements'];

        if (empty($tcaConfiguration) || !is_array($tcaConfiguration)) {
            throw new \InvalidArgumentException(
                'Content elements not configured for ' . $result['tableName'],
                1465680013
            );
        }
        
        $missing = array_diff(
            array_keys(array_flip(['foreign_table', 'foreign_field', 'position_field'])),
            array_keys($tcaConfiguration)
        );
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Content element ' . implode(',', $missing) . ' for ' . $result['tableName'] . ' is not set',
                1465680589
            );
        }

        if (empty($GLOBALS['TCA'][$tcaConfiguration['foreign_table']]['ctrl']['sortby'])) {
            throw new \InvalidArgumentException(
                'Order field for ' . $tcaConfiguration['foreign_table'] . ' is not set',
                1465681034
            );
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tcaConfiguration['foreign_table']);

        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);

        $queryBuilder->select('uid')
            ->from($tcaConfiguration['foreign_table'])
            ->where(
                $queryBuilder->expr()->eq(
                    $tcaConfiguration['foreign_field'],
                    empty($result['defaultLanguageRow']) ?
                        $result['databaseRow']['uid'] : $result['defaultLanguageRow']['uid']
                )
            )
            ->addOrderBy($GLOBALS['TCA'][$tcaConfiguration['foreign_table']]['ctrl']['sortby']);

        if (!empty($GLOBALS['TCA'][$tcaConfiguration['foreign_table']]['ctrl']['languageField'])) {
            $languageField = $GLOBALS['TCA'][$result['tableName']]['ctrl']['languageField'];
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA'][$tcaConfiguration['foreign_table']]['ctrl']['languageField'],
                    $languageField ? $result['databaseRow'][$languageField][0] : 0
                )
            );
        }

        if (!empty($tcaConfiguration['foreign_table_field'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $tcaConfiguration['foreign_table_field'],
                    $tcaConfiguration['foreign_table']
                )
            );
        }

        $elements = $queryBuilder->execute();

        $result['processedTca']['contentElements'] = [];

        while ($elementUid = $elements->fetchColumn()) {
            $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

            $formDataCompilerInput = [
                'tableName' => $tcaConfiguration['foreign_table'],
                'vanillaUid' => (int)$elementUid,
                'command' => 'edit',
                'returnUrl' => '',
            ];

            $formData = $formDataCompiler->compile($formDataCompilerInput);

            $columnPosition = $formData['databaseRow'][$tcaConfiguration['position_field']][0];
            $result['processedTca']['contentElements'][$columnPosition][] = $formData;
        }

        return $result;
    }
}
