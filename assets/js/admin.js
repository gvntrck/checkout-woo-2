/* global jQuery */
(function ($) {
    'use strict';

    $(function () {
        // Color picker.
        if ($.fn.wpColorPicker) {
            $('.cgv-color').wpColorPicker();
        }

        // Add field row.
        $('#cgv-add-field').on('click', function () {
            var tpl = $('#cgv-field-tpl').html();
            var index = Date.now();
            var html = tpl.replace(/__INDEX__/g, index);
            $('#cgv-fields-rows').append(html);
        });

        // Remove field row.
        $(document).on('click', '.cgv-remove-field', function () {
            $(this).closest('tr').remove();
        });

        // Drag-sort rows (lightweight, no jQuery UI dependency).
        var dragRow = null;
        $(document).on('mousedown', '.cgv-handle', function () {
            dragRow = $(this).closest('tr').get(0);
            if (dragRow) {
                dragRow.setAttribute('draggable', 'true');
            }
        });
        $(document).on('dragstart', '.cgv-field-row', function (e) {
            dragRow = this;
            try { e.originalEvent.dataTransfer.effectAllowed = 'move'; } catch (err) {}
        });
        $(document).on('dragover', '.cgv-field-row', function (e) {
            e.preventDefault();
            if (!dragRow || dragRow === this) { return; }
            var rect = this.getBoundingClientRect();
            var after = (e.originalEvent.clientY - rect.top) > rect.height / 2;
            if (after) {
                this.parentNode.insertBefore(dragRow, this.nextSibling);
            } else {
                this.parentNode.insertBefore(dragRow, this);
            }
        });
        $(document).on('dragend', '.cgv-field-row', function () {
            if (dragRow) {
                dragRow.removeAttribute('draggable');
            }
            dragRow = null;
        });
    });
})(jQuery);
