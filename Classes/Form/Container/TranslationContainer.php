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
 * Render a translation wizard
 *
 * This is an entry container called from controllers.
 */
class TranslationContainer extends AbstractContainer
{

    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($this->getTemplatePathAndFilename());

        uasort($this->data['systemLanguageRows'], function($a, $b) {
            return $a['title'] <=> $b['title'];
        });
        
        for ($i = 1; $i <= (int)$this->data['processedTca']['backendLayout']['rowCount']; $i++) {
            $row = $this->data['processedTca']['backendLayout']['rows'][$i];

            if (empty($row)) {
                continue;
            }

            for ($j = 1; $j <= (int)$this->data['processedTca']['backendLayout']['columnCount']; $j++) {

                if (!is_numeric($row['columns'][$j]['position'])) {
                    continue;
                }

                $column = $this->data['processedTca']['backendLayout']['columns'][(int)$row['columns'][$j]['position']];
                $cells = [];

                if (empty($column)) {
                    continue;
                }

                if ($column['assigned']) {
                    foreach ($this->data['languageUids'] as $language) {
                        $cells[] = $this->createCellData($language, $column);
                    }

                    $rows[] = [
                        'cells' => $cells
                    ];
                }
            }
        }
        
        $view->assignMultiple([
            'languages' => [$this->data['systemLanguageRows'][0]] + array_filter($this->data['systemLanguageRows'], function ($language) {
                return $language['uid'] > 0 && in_array($language['uid'], $this->data['languageUids']);
            }),
            'rows' => $rows,
            'uid' => $this->data['vanillaUid'],
            'tca' => [
                'container' => [
                    'table' => $this->data['tableName'],
                ],
                'element' => [
                    'table' => $this->data['processedTca']['contentElementTca']['foreign_table'],
                    'fields' => [
                        'position' => $this->data['processedTca']['contentElementTca']['position_field'],
                        'language' => $GLOBALS['TCA'][$this->data['processedTca']['contentElementTca']['foreign_table']]['ctrl']['languageField'],
                        'foreign' => [
                            'table' => $this->data['processedTca']['contentElementTca']['foreign_table_field'],
                            'field' => $this->data['processedTca']['contentElementTca']['foreign_field']
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
     * Create the data for a cell
     *
     * @param int $languageUid
     * @param array $column
     * @return array
     * @throws \TYPO3\CMS\Backend\Form\Exception
     */
    protected function createCellData($languageUid, $column) {
        $childHtml = '';

        foreach ((array)$this->data['processedTca']['contentElementPositions'][$column['position']] as $contentElement) {
            if ($contentElement['processedTca']['languageUid'] === $languageUid) {
                $options = array_merge($contentElement, [
                    'layoutColumn' => $column,
                    'renderType' => 'contentPreview',
                    'pageLayoutView' => $this->data['pageLayoutView'],
                    'showFlag' => (bool)$languageUid,
                    'returnUrl' => $this->data['returnUrl'],
                    'hasErrors' => !$contentElement['processedTca']['hasTranslations'] && $languageUid > 0 &&
                        !$this->data['allowInconsistentLanguageHandling']
                ]);

                $result = $this->nodeFactory->create($options)->render();

                $childHtml .= $result['html'];
            }
        }

        return [
            'uid' => $column['position'],
            'title' => $column['name'],
            'restricted' => $column['restricted'],
            'assigned' => $column['assigned'],
            'empty' => count($this->data['processedTca']['contentElementPositions'][(int)$column['position']]) === 0,
            'locked' => $column['locked'],
            'actions' => [
                'prependContentElement' => BackendUtility::getModuleUrl('record_edit', [
                    'edit' => [
                        $this->data['processedTca']['contentElementTca']['foreign_table'] => [
                            $this->data['defaultLanguageRow'] ? $this->data['defaultLanguageRow']['uid'] : $this->data['vanillaUid'] => 'new'
                        ]
                    ],
                    'defVals' => [
                        $this->data['processedTca']['contentElementTca']['foreign_table'] => [
                            $this->data['processedTca']['contentElementTca']['position_field'] => $column['position'],
                            $GLOBALS['TCA'][$this->data['processedTca']['contentElementTca']['foreign_table']]['ctrl']['languageField'] => $languageUid
                        ]
                    ],
                    'returnUrl' => $this->data['returnUrl']
                ])
            ],
            'childHtml' => $childHtml,
            'language' => $languageUid
        ];
    }

    /**
     * Get the template path and filename
     *
     * @return string
     */
    protected function getTemplatePathAndFilename() {
        return GeneralUtility::getFileAbsFileName(
            'EXT:wireframe/Resources/Private/Templates/Form/Container/Translation.html'
        );
    }
}