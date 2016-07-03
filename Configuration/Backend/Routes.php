<?php

/**
 * Definitions for routes provided by EXT:wireframe
 */
return [
    // Register content element controller
    'content_element' => [
        'path' => '/wireframe/content/',
        'target' => \TYPO3\CMS\Wireframe\Controller\ContentElementController::class . '::processRequest'
    ]
];