/* ----------------------------------------------------------------------
 * js/ca.ajaxsummarydisplay.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2023 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */

var caUI = caUI || {};

(function ($) {
    caUI.initAjaxSummaryDisplay = function(options) {
        // --------------------------------------------------------------------------------
        // setup options
        var that = jQuery.extend({
            controllerUrl: ''
        }, options);

        // --------------------------------------------------------------------------------
        // Define methods
        // --------------------------------------------------------------------------------
        // Load the display 'template' of sorts. ?>
        that.loadDisplay = function() {
            $.ajax({
                type: 'POST',
                url: that.controllerUrl + '/SummaryDisplay',
                data: $('#caSummaryDisplaySelectorForm').serialize(),
                error: (jqXHR, textStatus, errorThrown) => {
                    $('#summary-html-data-page ._error').html('Error: ' + textStatus);
                },
                success: (data) => {
                    $('#summary-html-data-page .content_wrapper').empty();
                    $('#summary-html-data-page .content_wrapper').html(data);

                    that.loadData();
                },
            });
        };
        // Load the actual data for the display 'template'.
        that.loadData = function() {
            $('#summary-html-data-page ._content').each(
                function() {
                    let id = $(this).attr('placementId');
                    $.ajax({
                        url: that.controllerUrl + '/summaryData',
                        data: $('#caSummaryDisplaySelectorForm').serialize() + '&placement_id=' + id,
                        error: (jqXHR, textStatus, errorThrown) => {
                            $('#summary-html-data-page ._error').html('Error: ' + textStatus);
                        },
                        success: (data) => {
                            $('#summary-html-data-page ._content[placementid="' + id + '"]').html(data);
                        },
                    });
                }
            );
        };

        $(document).ready(function () {
            // Show/hide the loading spinner div. ?>
            $(document).ajaxStart(() => {
                $('#summary-html-data-page ._error').empty();
                $('#summary-html-data-page ._indicator').show();
            });
            $(document).ajaxStop(() => $('#summary-html-data-page ._indicator').hide());

            that.loadDisplay();
        });

        $("#caSummaryDisplaySelectorForm").submit(function(e){
            e.preventDefault();
            that.loadDisplay();
            return false;
        });
        return that;
    };

})(jQuery);
