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

/**
 * Module: TYPO3/CMS/Wireframe/Sidebar
 * this JS code does the collapsing logic for the Sidebar
 * based on jQuery UI
 */
define(['jquery', 'jquery-ui/resizable'], function($) {
    'use strict';

    var Sidebar = {
        containerIdentifier: '.t3js-sidebar',
        toggleIdentifier: '.t3js-sidebar-toggle',
        borderIdentifier: '.t3js-sidebar-border',
        toggleStates: ['collapsed', 'expanded', 'full-expanded'],
        moduleIdentifier: '.module, .module-docheader',
        options: ['minWidth', 'maxWidth'],
        collapsedState: 'collapsed'
    };

    Sidebar.initialize = function() {
        var container = $(Sidebar.containerIdentifier);
        var toggle = $(Sidebar.toggleIdentifier);

        toggle.on('click', Sidebar.onToggled);

        if (container.attr('data-resizable') !== undefined) {
            container.resizable({
                handles: {
                    'w': Sidebar.borderIdentifier
                },
                resize: Sidebar.onResizing,
                stop: Sidebar.onResized,
                start: Sidebar.onResize
            });

            $.each(Sidebar.options, function(i, option) {
                if (container.data().hasOwnProperty(option)) {
                    container.resizable('option', option, container.data(option));
                }
            })
        }
    };

    Sidebar.onToggled = function() {
        var container = $(Sidebar.containerIdentifier);
        var module = $(Sidebar.moduleIdentifier);
        var state = container.attr('data-toggle');

        container.attr('data-toggle', Sidebar.getToggleState(state, 1));
        container.removeAttr('style');

        if (Sidebar.getToggleState(state, 2) === Sidebar.collapsedState) {
            container.removeAttr('data-expandable', '');
        } else {
            container.attr('data-expandable', '');

            if (container.attr('data-size')) {
                container.css('width', container.attr('data-size'));
            }
        }

        module.css('width', 'calc( 100% - ' + Sidebar.calculateWidth() + 'px )');
    };

    Sidebar.onResize = function() {
        var container = $(Sidebar.containerIdentifier);

        container.removeAttr('data-collapsed');
        container.attr('data-toggle', Sidebar.getToggleState(Sidebar.collapsedState, -1));
    };

    Sidebar.onResizing = function() {
        var width = Sidebar.calculateWidth();

        // See https://bugs.jqueryui.com/ticket/4985
        $(this).css('left', '');
        $(this).attr('data-size', width);

        $(Sidebar.moduleIdentifier).css('width', 'calc( 100% - ' + width + 'px )');
    };

    Sidebar.onResized = function() {
    };

    Sidebar.calculateWidth = function() {
        var border = $(Sidebar.borderIdentifier);
        var container = $(Sidebar.containerIdentifier);

        return (border.length > 0 ? container.outerWidth() + border.outerWidth() : container.outerWidth());
    };

    Sidebar.getToggleState = function(start, offset) {
        var i = $.inArray(start, Sidebar.toggleStates);
        var n = Sidebar.toggleStates.length;

        return Sidebar.toggleStates[((i + offset) % n + n) % n]
    };

    $(Sidebar.initialize);

    return Sidebar;
})