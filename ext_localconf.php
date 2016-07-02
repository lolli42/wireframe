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

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1466746112] = [
    'nodeName' => 'contentElementDefinitionsSidebar',
    'priority' => 40,
    'class' => \TYPO3\CMS\Wireframe\Form\Container\ContentElement\SidebarContainer::class
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1466746106] = [
    'nodeName' => 'contentPreview',
    'priority' => 40,
    'class' => \TYPO3\CMS\Wireframe\Form\Element\ContentElement\PreviewElement::class
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['contentContainer'] = array_merge(
    // @todo Remove all unused data provider
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'],
    [
        \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayout\Configuration::class => [
            'depends' => [
                \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Tca::class
            ]
        ],
        \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayout\ColumnAccessibility::class => [
            'depends' => [
                \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayout\Configuration::class
            ]
        ],
        \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayout\ColumnName::class => [
            'depends' => [
                \TYPO3\CMS\Wireframe\Form\Data\Provider\BackendLayout\Configuration::class
            ]
        ],
        \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Tca::class => [
            'depends' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class
            ]
        ],
        \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Inline::class => [
            'depends' => [
                \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Tca::class
            ]
        ],
        \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Positions::class => [
            'depends' => [
                \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Inline::class
            ]
        ]
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['contentElement'] = array_merge(
    [
        \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Visibility::class => [],
        \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Language::class => [],
        \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Actions::class => [],
        \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\FluidPreviewTemplate::class => [],
        \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Translation::class => [
            'depends' => [
                \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Language::class
            ]
        ],
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['contentElementDefinitions'] = [
    \TYPO3\CMS\Backend\Form\FormDataProvider\ReturnUrl::class => [],
    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class => [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\ReturnUrl::class
        ]
    ],
    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class => [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class
        ]
    ],
    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class => [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class
        ]
    ],
    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class => [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow::class,
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck::class
        ]
    ],
    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class => [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid::class
        ]
    ],
    \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class
        ]
    ],
    \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Tca::class => [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class
        ]
    ],
    \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Definitions::class => [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
            \TYPO3\CMS\Wireframe\Form\Data\Provider\ContentElement\Tca::class
        ]
    ],
];