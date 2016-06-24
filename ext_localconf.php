<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1466746089] = [
    'nodeName' => 'backendLayoutContainer',
    'priority' => 40,
    'class' => \TYPO3\CMS\Wireframe\Form\Container\BackendLayoutContainer::class
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1466746098] = [
    'nodeName' => 'translationContainer',
    'priority' => 40,
    'class' => \TYPO3\CMS\Wireframe\Form\Container\TranslationContainer::class
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1466746106] = [
    'nodeName' => 'contentPreview',
    'priority' => 40,
    'class' => \TYPO3\CMS\Wireframe\Form\Element\ContentPreviewElement::class
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['contentContainer'] = [
    'extends' => [
        'tcaDatabaseRecord'
    ],
    \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayoutConfiguration::class => [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class
        ]
    ],
    \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayoutColumnAccessibility::class => [
        'depends' => [
            \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayoutConfiguration::class
        ]
    ],
    \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayoutColumnName::class => [
        'depends' => [
            \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayoutConfiguration::class
        ]
    ],
    \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayoutColumnActions::class => [
        'depends' => [
            \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayoutColumnAccessibility::class
        ]
    ],
    \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElementRecords::class => [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaRecordTitle::class
        ]
    ],
    \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElementVisibility::class => [
        'depends' => [
            \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElementRecords::class
        ]
    ],
    \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElementActions::class => [
        'depends' => [
            \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElementRecords::class,
            \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayoutColumnAccessibility::class
        ]
    ],
    \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElementPreviewTemplate::class => [
        'depends' => [
            \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElementRecords::class
        ]
    ]
];