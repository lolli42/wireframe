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

use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection;
use TYPO3\CMS\Backend\View\BackendLayout\DefaultDataProvider;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Add backend layout for this record.
 */
class BackendLayoutConfiguration implements FormDataProviderInterface
{

    /**
     * Add backend layout configuration
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if (
            !in_array($result['tableName'], ['pages', 'pages_language_overlay']) &&
            !empty((array)$result['pageTsConfig']['TCEMAIN.']['table.'][$result['tableName'] . '.']['backendLayout.'])
        ) {
            $config = ArrayUtility::flatten(
                $result['pageTsConfig']['TCEMAIN.']['table.'][$result['tableName'] . '.']['backendLayout.']
            );

            $layout = BackendLayout::create(
                $result['tableName'],
                $result['tableName'],
                implode('\r\n', array_map(function ($key, $value) {
                    return $key . ' = ' . $value;
                }, $config))
            );
        } else {
            $dataProviderCollection = GeneralUtility::makeInstance(DataProviderCollection::class);

            $dataProviderCollection->add('default', DefaultDataProvider::class);

            foreach ((array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider'] as $key => $provider) {
                $dataProviderCollection->add($key, $provider);
            }

            if (in_array($result['tableName'], ['pages', 'pages_language_overlay'])) {
                $selected = !empty($result['databaseRow']['backend_layout']) ?
                    $result['databaseRow']['backend_layout'][0] : null;

                if ($selected === -1) {
                    $selected = false;
                } elseif (empty($selected)) {
                    $rootLine = $result['rootline'];
                    array_shift($rootLine);
                    array_pop($rootLine);
                    foreach ($rootLine as $page) {
                        $selected = (string)$page['backend_layout_next_level'];
                        if ($selected === '-1') {
                            $selected = false;
                            break;
                        } elseif ($selected !== '' && $selected !== '0') {
                            break;
                        }
                    }
                }
            } elseif (!empty((array)$result['pageTsConfig']['TCEMAIN.']['table.'][$result['tableName'] . '.']['backendLayout.'])) {
                $selected = $result['pageTsConfig']['TCEMAIN.']['table.'][$result['tableName'] . '.']['backendLayout'];
            } else {
                $selected = false;
            }

            $layout = $dataProviderCollection->getBackendLayout(empty($selected) ? 'default' : $selected, $result['vanillaUid']);

            if ($layout === null) {
                $layout = $dataProviderCollection->getBackendLayout('default', $result['vanillaUid']);
            }
        }

        if ($layout !== null) {
            $parser = GeneralUtility::makeInstance(TypoScriptParser::class);
            $conditionMatcher = GeneralUtility::makeInstance(ConditionMatcher::class);
            $parser->parse($parser->checkIncludeLines($layout->getConfiguration()), $conditionMatcher);

            $result['processedTca']['backendLayout'] = GeneralUtility::removeDotsFromTS($parser->setup['backend_layout.']);

            // restructure configuration
            foreach ($result['processedTca']['backendLayout']['rows'] as &$row) {
                $row['columns'] = (array)$row['columns'];

                foreach ($row['columns'] as $key => &$column) {
                    if (isset($column['colPos'])) {
                        $column['position'] = $column['colPos'];
                        unset($column['colPos']);
                    }

                    $position = is_numeric($column['position']) ? (int)$column['position'] : StringUtility::getUniqueId();
                    $result['processedTca']['backendLayout']['columns'][$position] = $column;

                    $row['columns'][$key] = [
                        'position' => $column['position']
                    ];
                }
            }
            $result['processedTca']['backendLayout']['columnCount'] = $result['processedTca']['backendLayout']['colCount'];
            unset($result['processedTca']['backendLayout']['colCount']);
        }

        return $result;
    }
}
