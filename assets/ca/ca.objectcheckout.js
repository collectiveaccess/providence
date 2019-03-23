/* ----------------------------------------------------------------------
 * js/ca.objectcheckout.js
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
			loadWidgetURL: null,
			
			removeButtonIcon: '(X)',
			
			cookieJar: jQuery.cookieJar('caBundleVisibility')
		}, options);
		
		jQuery('#' + that.transactionListContainerID).hide();
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
						if (object_id <= 0) {
							jQuery('#' + that.autocompleteID).val('');	// reset autocomplete to blank
							return false;
						}
					
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
							that.getInfoURL,
							{object_id: object_id, user_id: that.user_id}, function(data) {
								// on success add to transactionList display
								
								var _disp = '<div class="caLibraryTransactionListItemContainer"><div class="caLibraryTransactionListItemMedia">' + data.media + '</div><div class="caLibraryTransactionListItemName">' + data.title + "</div>";
								
								
								// Status values:
								// 	0 = available; 1 = out; 2 = out with reservations; 3 = available with reservations
								//
								_disp += '<div>Status: ' + data.status_display + '</div>';
								
								if (data.storage_location) {
									_disp += '<div>Location: ' + data.storage_location + '</div>';
								}
								
								// Show reservation details if item is not available and reserved by user other than the current one or not out with current user
								if (
									((data.status == 1) || (data.status == 2))
									&&
									!data.isOutWithCurrentUser && !data.isReservedByCurrentUser
								) {
									if ((that.user_id != data.current_user_id) && ((data.status == 1) || (data.status == 2))) {
										_disp += '<div class="caLibraryTransactionListItemWillReserve">' + data.reserve_display_label + ' (' + data.holder_display_label + ')</div>';
									}
									if ((that.user_id != data.current_user_id) && (data.status == 3)) {
										_disp += '<div class="caLibraryTransactionListItemWillReserve">' + data.reserve_display_label + '</div>';
									}
								}
								
								// Show notes and due date if item is available
								if ((data.status == 0) || (data.status == 3)) {
									// add note field
									_disp += '<div class="caLibraryTransactionListItemNotesContainer"><div class="caLibraryTransactionListItemNotesLabel">' + data.notes_display_label + '</div><textarea name="note" id="note_' + object_id + '" rows="2" cols="90"></textarea></div>';
								
									if (((data.status == 0) || (data.status == 3)) && (data.config.allow_override_of_due_dates == 1)) {	// item available so allow setting of due date
										_disp += '<div class="caLibraryTransactionListItemDueDateContainer"><div class="caLibraryTransactionListItemDueDateLabel">' + data.due_on_display_label + '</div><input type="text" name="due_date" id="dueDate_' + object_id + '" value="' + data.config.default_checkout_date + '" size="10"/></div>';
									}
								} else {
									_disp += '<br style="clear: both;"/>';
								}
								
								// remove button
								_disp += '<div class="caLibraryTransactionListItemRemoveButton"><a href="#" id="itemRemove_' + object_id + '" data-object_id="' + object_id + '">' + that.removeButtonIcon + '</a></div>';
								
								// support removal of items
								jQuery('#' + that.transactionListContainerID + ' .transactionList').append("<li id='item_" + object_id + "'>" + _disp + "</li>");
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
										jQuery('#' + that.transactionListContainerID).hide();
									}
								});
								that.itemList.push({
									object_id: object_id, due_date: null
								});
								jQuery('#dueDate_' + object_id).datepicker({minDate: 0, dateFormat: 'yy-mm-dd'});
								
								if(that.itemList.length > 0) {
									jQuery('#' + that.transactionSubmitContainerID).show();
									jQuery('#' + that.transactionListContainerID).show();
								} else {
									jQuery('#' + that.transactionSubmitContainerID).hide();
									jQuery('#' + that.transactionListContainerID).hide();
								}
								
								// reset autocomplete to blank
								jQuery('#' + that.autocompleteID).val('');
							}
						);
					}
				}
			}).click(function() { this.select(); }).focus();
			
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
								jQuery('#' + that.transactionListContainerID).hide();
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
								
								// reload left-hand side widget with new details for user
								if (that.loadWidgetURL && that.user_id) { jQuery('#widgets').load(that.loadWidgetURL, {user_id: that.user_id}); }
								
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
			
		});
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.xxx = function(id) {
			
		}
		
		
		// --------------------------------------------------------------------------------
		
		return that;
	};
})(jQuery);