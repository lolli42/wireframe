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
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Add content element definitions from PageTsConfig
 */
class Definitions implements FormDataProviderInterface
{
    /**
     * Add data
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $table = $result['tableName'];
        $pageTsConfig = $result['pageTsConfig'];

        if ($table === 'pages') {
            $groups = (array)$pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.'];
        } else {
            $groups = (array)$pageTsConfig['TCEFORM.']['table.'][$table . '.']
                [$result['processedTca']['contentContainerConfig']['column_name'] . '.']['definitions.'];
        }

        foreach ($groups as $group => $_) {
            $this->prepareDependencyOrdering($groups[$group], 'before');
            $this->prepareDependencyOrdering($groups[$group], 'after');
        }

        $groups = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($groups);

        $this->processLegacyHook($groups);

        foreach ($groups as $key => &$group) {
            $key = rtrim($key, '.');
            $group = (array)$group;

            $this->processLabels(['header'], $group);

            $result['processedTca']['contentElementDefinitions'][$key] = [
                'header' => $group['header'],
                'elements' => $this->processGroup(
                    $group,
                    $result['processedTca']['contentContainerConfig']['foreign_table'],
                    $pageTsConfig
                )
            ];
        }

        $result['processedTca']['contentElementDefinitions'] = array_filter(
            $result['processedTca']['contentElementDefinitions'],
            function ($group) {
                return !empty($group['elements']);
            }
        );

        return $result;
    }

    /**
     * @param array $group
     * @param string $table
     * @param array $pageTsConfig
     * @return array
     */
    protected function processGroup(array &$group, $table, array &$pageTsConfig)
    {
        $filter = $group['show'] === '*' ? true : GeneralUtility::trimExplode(',', $group['show'], true);
        $result = [];

        foreach ((array)$group['elements.'] as $key => &$definition) {
            $key = rtrim($key, '.');
            $definition = (array)$definition;

            if ($filter || in_array($key, $filter)) {
                $this->processLabels(['title', 'description'], $definition);
                $this->mapKeys([
                    'tt_content_defValues.' => 'defaultValues',
                    'defaultValues.' => 'defaultValues'
                ], $definition);

                if ($definition['params']) {
                    $parameters = GeneralUtility::explodeUrl2Array($definition['params'], true);

                    $definition['defaultValues'] = array_merge(
                        (array)$definition['defaultValues'],
                        (array)$parameters['defVals'][$table]
                    );

                    unset($definition['params']);
                }

                if ($this->isValidDefinition($definition, $table, $pageTsConfig)) {
                    $definition['key'] = $key;

                    foreach ((array)$definition['defaultValues'] as $column => $value) {
                        $definition['parameters'] .= is_array($GLOBALS['TCA'][$table]['columns'][$column]) ?
                            '&defVals[' . $table . '][' . $column . ']=' . rawurlencode($value) : '';
                    }

                    $result[$key] = $definition;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $groups
     * @return void
     */
    protected function processLegacyHook(array &$groups)
    {
        $elements = [];

        foreach ((array)$GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'] as $class => $path) {
            require_once $path;
            $elements = GeneralUtility::makeInstance($class)->proc($elements);
        }

        foreach ((array)$elements as $key => $element) {
            preg_match('/^[a-zA-Z0-9]+_/', $key, $group);
            $group = $group[0] ? substr($group[0], 0, -1) . '.' : $key;
            $groups[$group]['elements.'][substr($key, strlen($group)) . '.'] = $element;
        }
    }

    /**
     * @param array $definition
     * @param string $table
     * @param array $pageTsConfig
     * @return bool
     */
    protected function isValidDefinition(array &$definition, $table, array &$pageTsConfig)
    {
        $tceForm = &$pageTsConfig['TCEFORM.']['table.'][$table . '.'];

        foreach ((array)$definition['defaultValues'] as $column => $value) {
            if (is_array($GLOBALS['TCA'][$table]['columns'][$column])) {
                // Get information about if the field value is OK:
                $config = &$GLOBALS['TCA'][$table]['columns'][$column]['config'];
                $authModeDeny = $config['type'] == 'select' && $config['authMode']
                    && !$this->getBackendUser()->checkAuthMode($table, $column, $value, $config['authMode']);
                // explode TSconfig keys only as needed
                if (!isset($removeItems[$column])) {
                    $removeItems[$column] = GeneralUtility::trimExplode(
                        ',',
                        $tceForm[$column]['removeItems'],
                        true
                    );
                }
                if (!isset($keepItems[$column])) {
                    $keepItems[$column] = GeneralUtility::trimExplode(
                        ',',
                        $tceForm[$column]['keepItems'],
                        true
                    );
                }
                $isNotInKeepItems = !empty($keepItems[$column]) && !in_array($value, $keepItems[$column]);
                // @todo `CType` is specific for `tt_content`
                if ($authModeDeny || $column === 'CType' && (in_array($value, $removeItems[$column]) || $isNotInKeepItems)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param array $labels
     * @param array $configuration
     * @return void
     */
    protected function processLabels(array $labels, array &$configuration)
    {
        foreach ($labels as $label) {
            $configuration[$label] = $this->getLanguageService()->sL($configuration[$label]);
        }
    }

    /**
     * @param array $mappings
     * @param array $configuration
     * @return void
     */
    protected function mapKeys(array $mappings, array &$configuration)
    {
        foreach ($mappings as $source => $target) {
            $configuration[$target] = $configuration[$source] ? $configuration[$source] : $configuration[$target];
            unset($configuration[$source]);
        }
    }

    /**
     * @param array $configuration
     * @param string $key
     * @return void
     */
    protected function prepareDependencyOrdering(&$configuration, $key)
    {
        if (isset($configuration[$key])) {
            $configuration[$key] = GeneralUtility::trimExplode(',', $configuration[$key]);
            $configuration[$key] = array_map(function ($s) {return $s . '.';}, $configuration[$key]);
        }
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
