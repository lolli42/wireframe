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
 * Add backend layout configuration for this record
 */
class Configuration implements FormDataProviderInterface
{

    /**
     * Add data
     *
     * @param array $result
     * @return array
     * @todo Needs decomposition into `PageTsConfig` and `DataProviderApi`
     */
    public function addData(array $result)
    {
        $tableName = $result['tableName'];
        $columnName = $result['processedTca']['contentContainerConfig']['column_name'];
        $configuration = $result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$columnName . '.'];

        if ($tableName !== 'pages' && !empty($configuration['backendLayout.'])) {
            $configuration = ArrayUtility::flatten($configuration['backendLayout.']['config.']);

            $layout = BackendLayout::create(
                $result['tableName'] . '_' . $columnName,
                $result['tableName'] . '_' . $columnName,
                implode("\r\n", array_map(function ($key, $value) {
                    return $key . ' = ' . $value;
                }, array_keys($configuration), $configuration))
            );
        } else {
            $dataProviderCollection = GeneralUtility::makeInstance(DataProviderCollection::class);

            $dataProviderCollection->add('default', DefaultDataProvider::class);

            foreach ((array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider'] as $key => $provider) {
                $dataProviderCollection->add($key, $provider);
            }

            if ($result['tableName'] === 'pages') {
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
            } elseif (!empty($configuration['backendLayout'])) {
                $selected = $configuration['backendLayout'];
            } else {
                $selected = false;
            }

            $layout = $dataProviderCollection->getBackendLayout(empty($selected) ? 'default' : $selected, $result['vanillaUid']);

            if (!empty($selected) && $layout === null) {
                $layout = $dataProviderCollection->getBackendLayout('default', $result['vanillaUid']);
            }
        }

        if ($layout !== null) {
            $parser = GeneralUtility::makeInstance(TypoScriptParser::class);
            $conditionMatcher = GeneralUtility::makeInstance(ConditionMatcher::class);
            $parser->parse($parser->checkIncludeLines($layout->getConfiguration()), $conditionMatcher);

            // @todo Currently just a hack, an own key like `backendLayout` would be cleaner but currently not allowed because of exceptions `1438079402` and `1440601540`.
            $result['processedTca']['backendLayout'] = GeneralUtility::removeDotsFromTS((array)$parser->setup['backend_layout.']);
            $result['processedTca']['backendLayout']['rows'] = (array)$result['processedTca']['backendLayout']['rows'];
            $result['processedTca']['backendLayout']['columns'] = (array)$result['processedTca']['backendLayout']['columns'];
            
            // restructure configuration
            foreach ($result['processedTca']['backendLayout']['rows'] as &$row) {
                $row['columns'] = (array)$row['columns'];

                foreach ($row['columns'] as $key => &$column) {
                    if (isset($column['colPos'])) {
                        $column['position'] = $column['colPos'];
                        unset($column['colPos']);
                    } else {
                        $column['position'] = StringUtility::getUniqueId();
                    }

                    $result['processedTca']['backendLayout']['columns'][$column['position']] = $column;

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
