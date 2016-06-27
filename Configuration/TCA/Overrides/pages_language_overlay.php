<?php

defined('TYPO3_MODE') or die();

$GLOBALS['TCA']['pages_language_overlay']['columns']['content'] = [
    'config' => [
        'type' => 'inline',
        'foreign_table' => 'tt_content',
        'foreign_field' => 'pid',
        'position_field' => 'colPos'
    ]
];