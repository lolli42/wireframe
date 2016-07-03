<?php
namespace TYPO3\CMS\Wireframe\Form\Element\ContentElement;

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
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Wireframe\Form\Element\AbstractElement;

/**
 * Generates the preview of a content element
 *
 */
class PreviewElement extends AbstractElement
{
    /**
     * Render the preview
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $content = null;
        $legacyMode = $this->data['tableName'] === 'tt_content' && $this->data['pageLayoutView'] instanceof PageLayoutView;
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        
        $view->setTemplatePathAndFilename($this->getTemplatePathAndFilename());

        if ($legacyMode) {
            $header = $this->renderLegacyHeader();
            $content = $this->processLegacyHook($header);
        }

        if ($content === null) {
            $content = $this->renderContent();
        }

        if ($legacyMode && $content === null) {
            $content = $this->renderLegacyContent($header);
        }
        
        if ($this->data['displayLegacyActions']) {
            $this->data['processedTca']['actions'] = array_merge(
                $this->data['processedTca']['actions'],
                $this->createLegacyActions()
            );
        }

        $view->assignMultiple([
            'table' => $this->data['tableName'],
            'language' => $this->data['languageUid'],
            'flag' => $this->data['showFlag'] ? $this->data['systemLanguageRows'][$this->data['processedTca']['languageUid']]['flagIconIdentifier'] : '',
            'record' => $this->data['vanillaUid'],
            'actions' => $this->data['processedTca']['actions'],
            'position' => $this->data['layoutColumn']['position'],
            'visible' => $this->data['processedTca']['visible'],
            'errors' => $this->data['hasErrors'],
            'data' => $this->data['databaseRow'],
            'childHtml' => $header . '<span class="exampleContent">' . $content . '</span>'
        ]);

        $result['html'] = $view->render();

        return $result;
    }

    protected function getTemplatePathAndFilename() {
        return GeneralUtility::getFileAbsFileName(
            'EXT:wireframe/Resources/Private/Templates/Form/Element/ContentElement/Preview.html'
        );
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return IconFactory
     */
    protected function getIconFactory()
    {
        return GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return string
     */
    protected function renderContent()
    {
        try {
            $template = GeneralUtility::getFileAbsFileName($this->data['processedTca']['fluidPreviewTemplateFilename']);

            if (empty($template)) {
                return null;
            }

            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename($template);
            $view->assignMultiple($this->data['databaseRow']);

            if (!empty($this->data['databaseRow']['pi_flexform'])) {
                $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
                $view->assign(
                    'pi_flexform_transformed',
                    $flexFormService->convertFlexFormContentToArray($this->data['databaseRow']['pi_flexform'])
                );
            }
            return $view->render();
        } catch (\Exception $e) {
            // @todo log exception
        }

        return null;
    }

    /**
     * @return array
     * @deprecated
     */
    protected function createLegacyActions() {
        $actions = [];

        if ($this->data['disableContentElementWizard']) {
            $actions['insertAfter'] = BackendUtility::getModuleUrl(
                'record_edit',
                [
                    'edit' => [
                        $this->data['tableName'] => [
                            -$this->data['vanillaUid'] => 'new'
                        ]
                    ],
                    'returnUrl' => $this->data['returnUrl']
                ]
            );
        } else {
            $actions['insertAfter'] = BackendUtility::getModuleUrl(
                'content_element',
                [
                    'action' => 'createAction',
                    'containerTable' => $this->data['inlineParentTableName'],
                    'containerField' => $this->data['inlineParentFieldName'],
                    'containerUid' => $this->data['inlineParentUid'],
                    'columnPosition' => $this->data['layoutColumn']['position'],
                    'ancestorUid' => $this->data['vanillaUid'],
                    'languageUid' => $this->data['processedTca']['languageUid'],
                    'returnUrl' => $this->data['returnUrl']
                ]
            );
        }

        return $actions;
    }

    /**
     * @return string
     * @deprecated
     */
    protected function renderLegacyHeader()
    {
        $html = '';
        /** @var $view PageLayoutView */
        $view = $this->data['pageLayoutView'];

        if ($this->data['databaseRow']['header']) {
            $note = '';
            if ((int)$this->data['databaseRow']['header_layout'] === 100) {
                $note = ' <em>[' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.hidden')) . ']</em>';
            }
            $html = $this->data['databaseRow']['date'] ?
                htmlspecialchars($view->itemLabels['date'] . ' ' . BackendUtility::date($this->data['databaseRow']['date'])) . '<br />' : '';
            $html .= '<strong>' . $view->linkEditContent($view->renderText($this->data['databaseRow']['header']), $this->data['databaseRow']) .
                $note . '</strong><br />';
        }

        return $html;
    }

    /**
     * @param string $header
     * @return string
     * @deprecated
     */
    protected function processLegacyHook(&$header)
    {
        /** @var $view PageLayoutView */
        $view = $this->data['pageLayoutView'];
        $hooks = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'];
        $html = null;
        $draw = true;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');

        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);

        $row = $queryBuilder->select('*')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $this->data['vanillaUid']))
            ->execute()
            ->fetch();

        if (is_array($hooks)) {
            foreach ($hooks as $hookClass) {
                $hookObject = GeneralUtility::getUserObj($hookClass);
                if (!$hookObject instanceof PageLayoutViewDrawItemHookInterface) {
                    throw new \UnexpectedValueException(
                        $hookClass . ' must implement interface ' . PageLayoutViewDrawItemHookInterface::class,
                        1218547409);
                }
                $hookObject->preProcess($view, $draw, $header, $html, $row);
            }
        }

        return !$draw && $html === null ? '' : $html;
    }

    /**
     * @return string
     * @deprecated
     */
    protected function renderLegacyContent()
    {
        $renderText = function($text) {
            $text = strip_tags($text);
            $text = GeneralUtility::fixed_lgd_cs($text, 1500);
            return nl2br(htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8', false));
        };
        /** @var $view PageLayoutView */
        $view = $this->data['pageLayoutView'];
        $out = '';
        $lines = [];
        $labels = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');

        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);

        $row = $queryBuilder->select('*')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $this->data['vanillaUid']))
            ->execute()
            ->fetch();

        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $item) {
            $labels[$item[1]] = $this->getLanguageService()->sL($item[0]);
        }

        switch ($row['CType']) {
            case 'header':
                if ($row['subheader']) {
                    $lines[] = $renderText($row['subheader']);
                }
                break;
            case 'bullets':
            case 'table':
                if ($row['bodytext']) {
                    $lines[] = $renderText($row['bodytext']);
                }
                break;
            case 'uploads':
                if ($row['media']) {
                    $lines[] = $view->getThumbCodeUnlinked($row, 'tt_content', 'media');
                }
                break;
            case 'menu':
                $lines[] = '<strong>' . htmlspecialchars($labels[$row['CType']]) . '</strong>';
                $menuTypeLabel = $this->getLanguageService()->sL(
                    BackendUtility::getLabelFromItemListMerged($row['pid'], 'tt_content', 'menu_type',
                        $row['menu_type'])
                );
                $lines[] = $menuTypeLabel ?: 'invalid menu type';
                if ($row['menu_type'] !== '2' && ($row['pages'] || $row['selected_categories'])) {
                    //$out[] = ':' . $view->generateListForCTypeMenu($row);
                }
                break;
            case 'shortcut':
                if (!empty($row['records'])) {
                    $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                    $recordList = explode(',', $row['records']);
                    foreach ($recordList as $recordIdentifier) {
                        $split = BackendUtility::splitTable_Uid($recordIdentifier);
                        $tableName = empty($split[0]) ? 'tt_content' : $split[0];
                        $shortcutRecord = BackendUtility::getRecord($tableName, $split[1]);
                        if (is_array($shortcutRecord)) {
                            $icon = $iconFactory->getIconForRecord($tableName, $shortcutRecord, Icon::SIZE_SMALL)->render();
                            $icon = BackendUtility::wrapClickMenuOnIcon(
                                $icon,
                                $tableName,
                                $shortcutRecord['uid'],
                                1,
                                '',
                                '+copy,info,edit,view'
                            );
                            $lines[] = $icon
                                . htmlspecialchars(BackendUtility::getRecordTitle($tableName, $shortcutRecord));
                        }
                    }
                }
                break;
            case 'list':
                $hookArr = [];
                $hookOut = '';
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']])) {
                    $hookArr = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']];
                } elseif (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'])) {
                    $hookArr = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'];
                }
                if (!empty($hookArr)) {
                    $_params = ['pObj' => &$this, 'row' => $row, 'infoArr' => []];
                    foreach ($hookArr as $_funcRef) {
                        $hookOut .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                    }
                }
                if ((string)$hookOut !== '') {
                    $lines[] = $hookOut;
                } elseif (!empty($row['list_type'])) {
                    $label = BackendUtility::getLabelFromItemListMerged($row['pid'], 'tt_content', 'list_type',
                        $row['list_type']);
                    if (!empty($label)) {
                        $lines[] = '<strong>' . htmlspecialchars($this->getLanguageService()->sL($label)) . '</strong>';
                    } else {
                        $message = sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'),
                            $row['list_type']);
                        $lines[] = '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                    }
                } elseif (!empty($row['select_key'])) {
                    $lines[] = htmlspecialchars($this->getLanguageService()->sL(BackendUtility::getItemLabel('tt_content',
                            'select_key')))
                        . ' ' . $row['select_key'];
                } else {
                    $lines[] = '<strong>' . $this->getLanguageService()->getLL('noPluginSelected') . '</strong>';
                }
                $lines[] = $this->getLanguageService()->sL(
                    BackendUtility::getLabelFromItemlist('tt_content', 'pages', $row['pages']),
                    true
                );
                break;
            default:
                $contentType = $labels[$row['CType']];

                if (isset($contentType)) {
                    $lines[] = '<strong>' . htmlspecialchars($contentType) . '</strong>';
                    if ($row['bodytext']) {
                        $lines[] = $view->renderText($row['bodytext']);
                    }
                    if ($row['image']) {
                        $lines[] = $view->getThumbCodeUnlinked($row, 'tt_content', 'image');
                    }
                } else {
                    $message = sprintf(
                        $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'),
                        $row['CType']
                    );
                    $lines[] = '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                }
        }


        foreach ($lines as $line) {
            $out .= '<a href="' . $this->data['actions']['edit'] . '">' . $line . '</a><br/>';
        }

        return empty($out) ? null : $out;
    }
}
