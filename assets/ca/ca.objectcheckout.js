/* ----------------------------------------------------------------------
 * js/ca/ca.objectcheckout.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
	caUI.initObjectCheckoutManager = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			user_id: null,
			
			transactionListContainerID: 'transactionListContainer',
			transactionSubmitContainerID: 'transactionSubmitContainer',
			transactionResultsContainerID: 'transactionResultsContainer',
			
			autocompleteID: 'objectAutocomplete',
			
			itemList: [],
			
			searchURL: null,
			getInfoURL : null,
			saveTransactionURL: null,
			
			cookieJar: jQuery.cookieJar('caBundleVisibility')
		}, options);
		
		jQuery('#' + that.transactionSubmitContainerID).hide();
		jQuery('#' + that.transactionResultsContainerID).hide();
		
		jQuery('#' + that.autocompleteID).autocomplete( { 
				source: that.searchURL,
				minLength: 3, delay: 800, html: true,
				response: function(event, ui) {
					if ((ui.content.length == 1) && (ui.content[0].id > 0)) {
						ui.item = ui.content[0];
						jQuery(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
						jQuery(this).autocomplete('close');
					}
				},
				select: function(event, ui) {
					var object_id = ui.item.id;
					if (parseInt(object_id)) {
						var due_date = ui.item.due_date;
						
						// is it already on the list?
						var alreadySelected = false;
						jQuery.each(that.itemList, function(k, v) {
							if (v['object_id'] == object_id) { 
								alreadySelected = true;
								jQuery('#' + that.autocompleteID).val('');	// reset autocomplete to blank
								return false;
							}
						});
						if (alreadySelected) { return false; }
						
						// get object info
						jQuery.getJSON(
							that.getInfoURL + '/object_id/' + object_id,
							{}, function(data) {
								// on success add to transactionList display
								var _disp = data.media + ' ' + data.name + " (" + data.status_display + ")";
								if ((data.status == 0) && (data.config.allow_override_of_due_dates == 1)) {	// item available so allow setting of due date
									_disp += "<br/>Due on <input type='text' name='due_date' id='dueDate_" + object_id + "' value='" + data.config.default_checkout_date + "' size='10'/>";
								}
								
								// add note field
								_disp += "<br/>Notes <textarea name='note' id='note_" + object_id + "' rows='2' cols='90'></textarea>";
								
								// TODO: handle reservations?
								
								// support removal of items
								jQuery('#' + that.transactionListContainerID + ' .transactionList').append("<li id='item_" + object_id + "'>" + _disp + " <a href='#' id='itemRemove_" + object_id + "' data-object_id='" + object_id + "'>X</a></li>");
								jQuery('#itemRemove_' + object_id).on('click', function() {
									var object_id_to_delete = jQuery(this).data('object_id');
									jQuery('li#item_' + object_id_to_delete).remove();
									
									var newItemList = [];
									jQuery.each(that.itemList, function(k, v) {
										if (v['object_id'] != object_id_to_delete) {
											newItemList.push(v);
										}
									});
									that.itemList = newItemList;
									if (that.itemList.length == 0) {
										jQuery('#' + that.transactionSubmitContainerID).hide();
									}
								});
								that.itemList.push({
									object_id: object_id, due_date: null
								});
								jQuery('#dueDate_' + object_id).datepicker({minDate: 0, dateFormat: 'yy-mm-dd'});
								
								(that.itemList.length > 0) ? jQuery('#' + that.transactionSubmitContainerID).show() : jQuery('#' + that.transactionSubmitContainerID).hide();
								
								// reset autocomplete to blank
								jQuery('#' + that.autocompleteID).val('');
							}
						);
					}
				}
			});
			
			jQuery('#transactionSubmit').on('click', function(e) {
				// marshall transaction data and submit
				if (that.itemList.length > 0) {
					jQuery.each(that.itemList, function(k, v) {
						var object_id = v['object_id'];
						var due_date = jQuery('#dueDate_' + object_id).val();
						if (due_date) {
							that.itemList[k]['due_date'] = due_date;
						}
						var note = jQuery('#note_' + object_id).val();
						if (note) {
							that.itemList[k]['note'] = note;
						}
					});				
			
					jQuery.ajax({
						url: that.saveTransactionURL,
						type: 'POST',
						dataType: 'json',
						data: { user_id: that.user_id, item_list: JSON.stringify(that.itemList) },
						success: function(data) {
								//console.log('Success', data);
						
								// clear item list
								jQuery('#' + that.transactionListContainerID + ' .transactionList li').remove();
								that.itemList = [];
								jQuery('#' + that.transactionSubmitContainerID).hide();
								jQuery('#' + that.autocompleteID).focus();
						
								// clear transaction results
								jQuery('#' + that.transactionResultsContainerID + ' .transactionSuccesses li').remove();
								jQuery('#' + that.transactionResultsContainerID + ' .transactionErrors li').remove();
						
								// show results of transaction submission
								if (data.checkouts) {
									jQuery('#' + that.transactionResultsContainerID + ' .transactionSuccesses li').remove();
									jQuery.each(data.checkouts, function(k, v) {
										jQuery('#' + that.transactionResultsContainerID + ' .transactionSuccesses').append("<li>" + v + "</li>");
									});
								}
								if (data.errors) {
									jQuery('#' + that.transactionResultsContainerID + ' .transactionErrors li').remove();
									jQuery.each(data.errors, function(k, v) {
										jQuery('#' + that.transactionResultsContainerID + ' .transactionErrors').append("<li>" + v + "</li>");
									});
								}
								jQuery('#' + that.transactionResultsContainerID).fadeIn(250);
								setTimeout(function() {
									jQuery('#' + that.transactionResultsContainerID).fadeOut(250);
								}, 5000);
							},
						error: function( jqxhr, textStatus, error ) {
								var err = textStatus + ", " + error;
								console.log( "Request Failed: " + err );
							}
					});
				}
			
		}).click(function() { this.select(); }).focus();
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.xxx = function(id) {
			
		}
		
		
		// --------------------------------------------------------------------------------
		
		return that;
	};
})(jQuery);