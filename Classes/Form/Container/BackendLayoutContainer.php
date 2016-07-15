<?php
namespace TYPO3\CMS\Wireframe\Form\Container;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Render a backend layout
 *
 * This is an entry container called from controllers.
 */
class BackendLayoutContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @todo Create some kind of a reusable iterator utility for layouts
     */
    public function render()
    {
        $layout = $this->data['processedTca']['backendLayout'];
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $columns = array_fill(
            0,
            (int)$layout['columnCount'],
            ['width' => 100 / max((int)$layout['columnCount'], 1)]
        );
        $rows = [];

        $view->setTemplatePathAndFilename($this->getTemplatePathAndFilename());

        for ($i = 1; $i <= (int)$layout['rowCount']; $i++) {
            $row = $layout['rows'][$i];
            $cells = [];

            if (empty($row)) {
                continue;
            }

            for ($j = 1; $j <= (int)$layout['columnCount']; $j++) {
                $column = $layout['columns'][$row['columns'][$j]['position']];
                $childHtml = '';

                if (empty($column)) {
                    continue;
                }

                if ($column['assigned']) {
                    foreach ((array)$this->data['processedTca']['contentElementPositions'][$column['position']] as $contentElement) {
                        if ($contentElement['processedTca']['languageUid'] === $this->data['languageUid']) {
                            $options = array_merge($contentElement, [
                                'layoutColumn' => $column,
                                'renderType' => 'contentElementPreview',
                                'pageLayoutView' => $this->data['pageLayoutView'],
                                'showFlag' => (bool)$this->data['languageUid'],
                                'returnUrl' => $this->data['returnUrl'],
                                'hasErrors' => !$contentElement['processedTca']['hasTranslations'] && $this->data['languageUid'] > 0 &&
                                    !$this->data['allowInconsistentLanguageHandling'],
                                'displayLegacyActions' => $this->data['displayLegacyActions']
                            ]);

                            $result = $this->nodeFactory->create($options)->render();

                            $childHtml .= $result['html'];
                        }
                    }
                }

                $cells[] = [
                    'uid' => $column['position'],
                    'title' => $column['name'],
                    'restricted' => $column['restricted'],
                    'assigned' => $column['assigned'],
                    'empty' => count($this->data['processedTca']['contentElementPositions'][(int)$column['position']]) === 0,
                    'locked' => $column['locked'],
                    'actions' => $this->data['displayLegacyActions'] ? $this->createLegacyActions($column['position']) : [],
                    'childHtml' => $childHtml,
                    'columnSpan' => (int)$column['colspan'],
                    'rowSpan' => (int)$column['rowspan']
                ];
            }

            $rows[] = [
                'cells' => $cells
            ];
        }

        $view->assignMultiple([
            'columns' => $columns,
            'rows' => $rows,
            'uid' => $this->data['vanillaUid'],
            'pid' => $this->data['effectivePid'],
            'language' => $this->data['languageUid'],
            'tca' => [
                'container' => [
                    'table' => $this->data['tableName'],
                ],
                'element' => [
                    'table' => $this->data['processedTca']['contentContainerConfig']['foreign_table'],
                    'fields' => [
                        'position' => $this->data['processedTca']['contentContainerConfig']['position_field'],
                        'language' => $GLOBALS['TCA'][$this->data['processedTca']['contentContainerConfig']['foreign_table']]['ctrl']['languageField'],
                        'foreign' => [
                            'table' => $this->data['processedTca']['contentContainerConfig']['foreign_table_field'],
                            'field' => $this->data['processedTca']['contentContainerConfig']['foreign_field']
                        ]
                    ]
                ]
            ]
        ]);

        return array_merge($this->initializeResultArray(), [
            'requireJsModules' => [
                'TYPO3/CMS/Backend/Tooltip',
                'TYPO3/CMS/Backend/Localization',
                'TYPO3/CMS/Backend/ClickMenu',
                'TYPO3/CMS/Backend/Modal',
                'TYPO3/CMS/Wireframe/DragDrop'
            ],
            'stylesheetFiles' => [
                ExtensionManagementUtility::extRelPath('wireframe') . 'Resources/Public/Css/DragDrop.css',
                ExtensionManagementUtility::extRelPath('wireframe') . 'Resources/Public/Css/BackendLayout.css'
            ],
            'html' => $view->render()
        ]);
    }

    /**
     * @return string
     */
    protected function getTemplatePathAndFilename()
    {
        return GeneralUtility::getFileAbsFileName(
            'EXT:wireframe/Resources/Private/Templates/Form/Container/BackendLayout.html'
        );
    }

    /**
     * @param int $position
     * @return array
     */
    protected function createLegacyActions($position)
    {
        $actions = [];

        if ($this->data['disableContentElementWizard']) {
            $actions['prependContentElement'] = BackendUtility::getModuleUrl(
                'record_edit',
                [
                    'edit' => [
                        $this->data['processedTca']['contentContainerConfig']['foreign_table'] => [
                            $this->data['vanillaUid'] => 'new'
                        ]
                    ],
                    'defVals' => [
                        $this->data['processedTca']['contentContainerConfig']['foreign_table'] => [
                            $this->data['processedTca']['contentContainerConfig']['position_field'] => $position,
                            $this->data['processedTca']['contentElementTca']['ctrl']['languageField'] => $this->data['languageUid']
                        ]
                    ],
                    'returnUrl' => $this->data['returnUrl']
                ]
            );
        } else {
            $actions['prependContentElement'] = BackendUtility::getModuleUrl(
                'content_element',
                [
                    'action' => 'createAction',
                    'containerTable' => $this->data['tableName'],
                    'containerField' => $this->data['processedTca']['contentContainerConfig']['column_name'],
                    'containerUid' => $this->data['vanillaUid'],
                    'columnPosition' => $position,
                    'languageUid' => $this->data['languageUid'],
                    'returnUrl' => $this->data['returnUrl']
                ]
            );
        }

        return $actions;
    }
}
