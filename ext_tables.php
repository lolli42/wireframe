<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'TYPO3.CMS.Wireframe',
        'web',
        'page_layout',
        'top',
        [
            'PageLayout' => 'index, translate'
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:backend/Resources/Public/Icons/module-page.svg',
            'labels' => [
                'title' => 'LLL:EXT:wireframe/Resources/Private/Language/ext_tables:module.layout.title',
                'description' => 'LLL:EXT:wireframe/Resources/Private/Language/ext_tables:module.layout.description.long',
                'shortdescription' => 'LLL:EXT:wireframe/Resources/Private/Language/ext_tables:module.layout.description.short'
            ]
        ]
    );
}
