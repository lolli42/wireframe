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

use TYPO3\CMS\Backend\Form\Container\AbstractContainer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Render header and footer row.
 *
 * This is an entry container called from controllers.
 * It wraps the title and a footer around the main html.
 * It either calls a FullRecordContainer or ListOfFieldsContainer to render
 * a full record or only some fields from a full record.
 */
class BackendLayoutContainer extends AbstractContainer
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

        $columns = array_fill(
            0,
            (int)$this->data['processedTca']['backendLayout']['columnCount'],
            ['width' => 100 / (int)$this->data['processedTca']['backendLayout']['columnCount']]
        );
        $rows = [];
        
        for ($i = 1; $i <= (int)$this->data['processedTca']['backendLayout']['rowCount']; $i++) {
            $row = $this->data['processedTca']['backendLayout']['rows'][$i];
            $cells = [];

            if (empty($row)) {
                continue;
            }

            for ($j = 1; $j <= (int)$this->data['processedTca']['backendLayout']['columnCount']; $j++) {

                if (!is_numeric($row['columns'][$j]['position'])) {
                    continue;
                }

                $column = $this->data['processedTca']['backendLayout']['columns'][(int)$row['columns'][$j]['position']];
                $childHtml = '';

                if (empty($column)) {
                    continue;
                }

                if ($column['assigned']) {
                    foreach ((array)$this->data['processedTca']['contentElements'][$column['position']] as $contentElement) {
                        $options = $contentElement;
                        $options['languageUid'] = $this->data['languageUid'];
                        $options['layoutColumn'] = $column;
                        $options['renderType'] = 'contentPreview';
                        $options['pageLayoutView'] = $this->data['pageLayoutView'];

                        $result = $this->nodeFactory->create($options)->render();

                        $childHtml .= $result['html'];
                    }
                }

                $cells[] = [
                    'uid' => $column['position'],
                    'title' => $column['name'],
                    'restricted' => $column['restricted'],
                    'assigned' => $column['assigned'],
                    'empty' => count($this->data['processedTca']['contentElements'][(int)$column['position']]) === 0,
                    'locked' => $column['locked'],
                    'actions' => $column['actions'],
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
            'language' => $this->data['languageUid'],
            'tca' => [
                'container' => [
                    'table' => $this->data['tableName'],
                ],
                'element' => [
                    'table' => $this->data['processedTca']['ctrl']['EXT']['wireframe']['content_elements']['foreign_table'],
                    'fields' => [
                        'position' => $this->data['processedTca']['ctrl']['EXT']['wireframe']['content_elements']['position_field'],
                        'language' => $GLOBALS['TCA'][$this->data['processedTca']['ctrl']['EXT']['wireframe']['content_elements']['foreign_table']]['ctrl']['languageField'],
                        'foreign' => [
                            'table' => $this->data['processedTca']['ctrl']['EXT']['wireframe']['content_elements']['foreign_table_field'],
                            'field' => $this->data['processedTca']['ctrl']['EXT']['wireframe']['content_elements']['foreign_field']
                        ]
                    ]
                ]
            ]
        ]);

        $result['requireJsModules'] = [
            'TYPO3/CMS/Backend/Tooltip',
            'TYPO3/CMS/Backend/Localization',
            'TYPO3/CMS/Backend/ClickMenu',
            'TYPO3/CMS/Backend/Modal',
            'TYPO3/CMS/Wireframe/DragDrop'
        ];
        $result['additionalJavaScriptPost'] = [];
        $result['additionalJavaScriptSubmit'] = [];
        $result['additionalHiddenFields'] = [];
        $result['stylesheetFiles'] = [
            ExtensionManagementUtility::extRelPath('wireframe') . 'Resources/Public/Css/Default.css'
        ];
        $result['html'] = $view->render();

        return $result;
    }

    protected function getTemplatePathAndFilename() {
        return GeneralUtility::getFileAbsFileName(
            'EXT:wireframe/Resources/Private/Templates/Form/Container/BackendLayout.html'
        );
    }
}
