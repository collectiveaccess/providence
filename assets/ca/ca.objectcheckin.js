/* ----------------------------------------------------------------------
 * js/ca/ca.objectcheckin.js
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
	caUI.initObjectCheckinManager = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			
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
					var checkout_id = ui.item.id;
					if (parseInt(checkout_id)) {
						if (checkout_id <= 0) {
							jQuery('#' + that.autocompleteID).val('');	// reset autocomplete to blank
							return false;
						}
						
						// is it already on the list?
						var alreadySelected = false;
						jQuery.each(that.itemList, function(k, v) {
							if (v['checkout_id'] == checkout_id) { 
								alreadySelected = true;
								jQuery('#' + that.autocompleteID).val('');	// reset autocomplete to blank
								return false;
							}
						});
						if (alreadySelected) { return false; }
						
						// get checkout info
						jQuery.getJSON(
							that.getInfoURL + '/checkout_id/' + checkout_id,
							{}, function(data) {
								// on success add to transactionList display
								var _disp = '<div class="caLibraryTransactionListItemContainer"><div class="caLibraryTransactionListItemMedia">' + data.media + '</div><div class="caLibraryTransactionListItemName">' + data.title + "</div>";
								_disp += '<div class="caLibraryTransactionListItemBorrower">' + data.borrower + "</div>";
								
								// add note field
								_disp += '<div class="caLibraryTransactionListItemNotesContainer"><div class="caLibraryTransactionListItemNotesLabel">Notes</div><textarea name="note" id="note_' + checkout_id + '" rows="2" cols="90"></textarea></div>';
								
								// add remove button
								_disp += '<div class="caLibraryTransactionListItemRemoveButton"><a href="#" id="itemRemove_' + checkout_id + '" data-checkout_id="' + checkout_id + '">' + that.removeButtonIcon + '</a>';
								
								// support removal of items
								jQuery('#' + that.transactionListContainerID + ' .transactionList').append("<li id='item_" + checkout_id + "'>" + _disp + "</li>");
								jQuery('#itemRemove_' + checkout_id).on('click', function() {
									var checkout_id_to_delete = jQuery(this).data('checkout_id');
									jQuery('li#item_' + checkout_id_to_delete).remove();
									
									var newItemList = [];
									jQuery.each(that.itemList, function(k, v) {
										if (v['checkout_id'] != checkout_id_to_delete) {
											newItemList.push(v);
										}
									});
									that.itemList = newItemList;
									if (that.itemList.length == 0) {
										jQuery('#' + that.transactionListContainerID).hide();
										jQuery('#' + that.transactionSubmitContainerID).hide();
									}
								});
								that.itemList.push({
									checkout_id: checkout_id
								});
								
								if (that.itemList.length > 0) {
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
						var checkout_id = v['checkout_id'];
						var note = jQuery('#note_' + checkout_id).val();
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
								if (data.checkins) {
									jQuery('#' + that.transactionResultsContainerID + ' .transactionSuccesses li').remove();
									jQuery.each(data.checkins, function(k, v) {
										jQuery('#' + that.transactionResultsContainerID + ' .transactionSuccesses').append("<li>" + v + "</li>");
									});
								}
								if (data.errors) {
									jQuery('#' + that.transactionResultsContainerID + ' .transactionErrors li').remove();
									jQuery.each(data.errors, function(k, v) {
										jQuery('#' + that.transactionResultsContainerID + ' .transactionErrors').append("<li>" + v + "</li>");
									});
								}
								
								// reload left-hand side widget with new details
								if (that.loadWidgetURL) { jQuery('#widgets').load(that.loadWidgetURL); }
								
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