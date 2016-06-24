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
 * Module: TYPO3/CMS/Wireframe/DragDrop
 * this JS code does the drag+drop logic for the Wireframe Component
 * based on jQuery UI
 */
define(['jquery', 'jquery-ui/droppable'], function ($) {
	'use strict';

	/**
	 *
	 * @type {{containerIdentifier: string, contentIdentifier: string, dragIdentifier: string, dragHeaderIdentifier: string, dropZoneIdentifier: string, columnIdentifier: string, validDropZoneClass: string, dropPossibleHoverClass: string, addContentIdentifier: string, originalStyles: string}}
	 * @exports TYPO3/CMS/Backend/LayoutModule/DragDrop
	 */
	var DragDrop = {
		containerIdentifier: '.t3js-content-container',
		contentIdentifier: '.t3js-content-element',
		dragIdentifier: '.t3-content-element-dragitem',
		dragHeaderIdentifier: '.t3js-content-element-draghandle',
		dropZoneIdentifier: '.t3js-content-element-dropzone-available',
		columnIdentifier: '.t3js-backend-layout-column',
		validDropZoneClass: 'active',
		dropPossibleHoverClass: 't3-content-element-dropzone-possible',
		addContentIdentifier: '.t3js-content-new-element',
		clone: true,
		originalStyles: ''
	};

	/**
	 * initializes Drag+Drop for all content elements on the container
	 */
	DragDrop.initialize = function() {
		$(DragDrop.contentIdentifier).each(function() {
			$(this).draggable({
				handle: this.dragHeaderIdentifier,
				scope: $(this).closest(DragDrop.containerIdentifier).data('table'),
				cursor: 'move',
				distance: 20,
				addClasses: 'active-drag',
				revert: 'invalid',
				zIndex: 100,
				start: function (evt, ui) {
					DragDrop.onDragStart($(this));
				},
				stop: function (evt, ui) {
					DragDrop.onDragStop($(this));
				}
			})
		});

		$(DragDrop.dropZoneIdentifier).each(function() {
			$(this).droppable({
				accept: this.contentIdentifier,
				scope: $(this).closest(DragDrop.containerIdentifier).data('table'),
				tolerance: 'pointer',
				over: function (evt, ui) {
					DragDrop.onDropHoverOver($(ui.draggable), $(this));
				},
				out: function (evt, ui) {
					DragDrop.onDropHoverOut($(ui.draggable), $(this));
				},
				drop: function (evt, ui) {
					DragDrop.onDrop($(ui.draggable), $(this), evt);
				}
			})
		});
	};

	/**
	 * called when a draggable is selected to be moved
	 * @param $element a jQuery object for the draggable
	 * @private
	 */
	DragDrop.onDragStart = function ($element) {
		// Add css class for the drag shadow
		DragDrop.originalStyles = $element.get(0).style.cssText;
		$element.children(DragDrop.dragIdentifier).addClass('dragitem-shadow');
		$element.append('<div class="ui-draggable-copy-message">' + TYPO3.lang['dragdrop.copy.message'] + '</div>');
		// Hide create new element button
		$element.children(DragDrop.dropZoneIdentifier).addClass('drag-start');
		$element.closest(DragDrop.columnIdentifier).removeClass('active');

		$element.parents(DragDrop.containerIdentifier).find(DragDrop.addContentIdentifier).hide();
		$element.find(DragDrop.dropZoneIdentifier).hide();

		// make the drop zones visible
		$(DragDrop.dropZoneIdentifier).each(function () {
			if (
				$(this).parent().find('.icon-actions-document-new').length
			) {
				$(this).addClass(DragDrop.validDropZoneClass);
			} else {
				$(this).closest(DragDrop.contentIdentifier).find('> ' + DragDrop.addContentIdentifier + ', > > ' + DragDrop.addContentIdentifier).show();
			}
		});
	};

	/**
	 * called when a draggable is released
	 * @param $element a jQuery object for the draggable
	 * @private
	 */
	DragDrop.onDragStop = function ($element) {
		// Remove css class for the drag shadow
		$element.children(DragDrop.dragIdentifier).removeClass('dragitem-shadow');
		// Show create new element button
		$element.children(DragDrop.dropZoneIdentifier).removeClass('drag-start');
		$element.closest(DragDrop.columnIdentifier).addClass('active');
		$element.parents(DragDrop.containerIdentifier).find(DragDrop.addContentIdentifier).show();
		$element.find(DragDrop.dropZoneIdentifier).show();
		$element.find('.ui-draggable-copy-message').remove();

		// Reset inline style
		$element.get(0).style.cssText = DragDrop.originalStyles;

		$(DragDrop.dropZoneIdentifier + '.' + DragDrop.validDropZoneClass).removeClass(DragDrop.validDropZoneClass);
	};

	/**
	 * adds CSS classes when hovering over a dropzone
	 * @param $draggable
	 * @param $droppable
	 * @private
	 */
	DragDrop.onDropHoverOver = function ($draggable, $droppable) {
		if ($droppable.hasClass(DragDrop.validDropZoneClass)) {
			$droppable.addClass(DragDrop.dropPossibleHoverClass);
		}
	};

	/**
	 * removes the CSS classes after hovering out of a dropzone again
	 * @param $draggable
	 * @param $droppable
	 * @private
	 */
	DragDrop.onDropHoverOut = function ($draggable, $droppable) {
		$droppable.removeClass(DragDrop.dropPossibleHoverClass);
	};

	/**
	 * this method does the whole logic when a draggable is dropped on to a dropzone
	 * sending out the request and afterwards move the HTML element in the right place.
	 *
	 * @param $draggable
	 * @param $droppable
	 * @param {Event} event the event
	 * @private
	 */
	DragDrop.onDrop = function ($draggable, $droppable, event) {
		$droppable.removeClass(DragDrop.dropPossibleHoverClass);
		var $pasteAction = typeof $draggable === 'number';
		// send an AJAX request via the AjaxDataHandler
		var element = $pasteAction ? $draggable : parseInt($draggable.data('uid'));
		if (element > 0) {
			var tca = $droppable.closest('[data-tca]').data('tca');
			// if the item was moved to the top of the cell, use the container uid instead
			var target = !$droppable.closest(DragDrop.contentIdentifier).attr('data-uid') ?
				$droppable.closest(DragDrop.containerIdentifier).data('uid') :
				0 - parseInt($droppable.closest(DragDrop.contentIdentifier).data('uid'));
			var language = parseInt($droppable.closest('[data-language]').data('language'));
			var position = 0;
			var copyAction = event && event.originalEvent.ctrlKey;
			var parameters = {
				cmd: {},
				data: {}
			};

			parameters['cmd'][tca.element.table] = {};
			parameters['data'][tca.element.table] = {};

			if (target !== 0) {
				position = $droppable.closest(DragDrop.columnIdentifier).data('uid');
			}

			if (copyAction) {
				parameters['cmd'][tca.element.table][element] = {
					copy: {
						action: 'paste',
						target: target, // @todo not sure about this regarding foreign_field
						update: {}
					}
				};
				parameters['cmd'][tca.element.table][element]['copy']['update'][tca.element.fields.position] = position;
				parameters['cmd'][tca.element.table][element]['copy']['update'][tca.element.fields.language] = language;
				if (tca.element.fields.foreign.table) {
					parameters['cmd'][tca.element.table][element]['copy']['update'][tca.element.fields.foreign.table] =
						tca.container.table;
				}
				if (tca.element.fields.foreign.field) {
					parameters['cmd'][tca.element.table][element]['copy']['update'][tca.element.fields.foreign.field] =
						$droppable.closest(DragDrop.containerIdentifier).data('uid');
				}
			} else {
				parameters['data'][tca.element.table][element] = {};
				parameters['data'][tca.element.table][element][tca.element.fields.position] = position;
				parameters['data'][tca.element.table][element][tca.element.fields.language] = language;
				if (tca.element.fields.foreign.table) {
					parameters['data'][tca.element.table][element][tca.element.fields.foreign.table] =
						tca.container.table;
				}
				if (tca.element.fields.foreign.field) {
					parameters['data'][tca.element.table][element][tca.element.fields.foreign.field] =
						$droppable.closest(DragDrop.containerIdentifier).data('uid');
				}
				parameters['cmd'][tca.element.table][element] = {move: target};
			}
			// fire the request, and show a message if it has failed
			DragDrop.ajaxAction($droppable, $draggable, parameters, copyAction);
		}
	};

	/**
	 * this method does the actual AJAX request for both, the  move and the copy action.
	 *
	 * @param $droppable
	 * @param $draggable
	 * @param parameters
	 * @param copyAction
	 * @private
	 */
	DragDrop.ajaxAction = function ($droppable, $draggable, parameters, copyAction) {
		require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
			DataHandler.process(parameters).done(function (result) {
				if (!result.hasErrors) {
					// insert draggable on the new position
					if (!$droppable.parent().hasClass(DragDrop.contentIdentifier.substring(1))) {
						$draggable.detach().css({top: 0, left: 0})
							.insertAfter($droppable.closest(DragDrop.dropZoneIdentifier));
					} else {
						$draggable.detach().css({top: 0, left: 0})
							.insertAfter($droppable.closest(DragDrop.contentIdentifier));
					}
					// should be always reloaded otherwise the history back of the browser doesn't work correctly
					self.location.reload(true);
				}
			});
		});
	};

	$(DragDrop.initialize);
	return DragDrop;
});
