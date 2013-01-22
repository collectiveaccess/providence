<?php
/* ----------------------------------------------------------------------
 * themes/default/views/client/library/checkout_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2013 Whirl-i-Gig
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
 
	$t_order 			= $this->getVar('t_order');
	$vn_order_id		= (int)$t_order->getPrimaryKey();
	$vn_transaction_id 	= $this->getVar('transaction_id');
	$va_errors 			= $this->getVar('errors');
	
	$t_order_item 		= $this->getVar('t_order_item');
	
	$va_failed_inserts 	= $this->getVar('failed_insert_list');		// List of values for items that failed on creation attempt
	$va_default_values 	= $this->getVar('default_values');			// Default values for various item fields
	
	$vb_loan_use_item_fee_and_tax = (bool)$this->getVar('loan_use_item_fee_and_tax');
	$vb_loan_use_notes_and_restrictions = (bool)$this->getVar('loan_use_notes_and_restrictions');
	$vb_loan_use_additional_fees = (bool)$this->getVar('loan_use_additional_fees');
	
	$vs_currency_symbol = $this->getVar('currency_symbol');
	$vs_currency_input_format = "<div class='formLabel'>^LABEL<br/>{$vs_currency_symbol}^ELEMENT</div>";
	
	$vs_id_prefix = 'item_list';
	
	$va_initial_values = $this->getVar('order_items');
	
	$vn_max_field_width = 50;
?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		(caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Check-out"), 'caClientLibraryCheckoutForm')).' '.
		(caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'client/library', 'CheckOut', 'Index', array('order_id' => 0))),
		'',
		''
	);
	
	print caFormTag($this->request, 'Save', 'caClientLibraryCheckoutForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true, 'noTimestamp' => true));	
?>
		<div class="formLabel">
			<?php print _t('Client name'); ?>:
			<input type="text" size="60" name="client_autocomplete" value="<?php print $t_order->getOrderTransactionUserName(); ?>" id="client_autocomplete" class="lookupBg"/>
			<input type="hidden" name="transaction_user_id" id="transaction_user_id" value="<?php print ($t_user = $t_order->getOrderTransactionUserInstance()) ? $t_user->getPrimaryKey() : ''; ?>"/>
		
			<a href="#" onclick="jQuery('#caClientLibraryCustomerInfoForm').slideToggle(300);" class='button' id='caClientLibraryCustomerInfoMoreButton'><?php print _t('Contact information'); ?> &rsaquo;</a>
			<div class="formContainerBg " style="padding-top:0px;" id="caClientLibraryCustomerInfoForm">
				<table width="100%">
					<tr>
						<td>
							<h2><?php print _t('Billing'); ?></h2>
							<div id='caBillingFields'>
			<?php
					$va_billing_fields = array(
						"billing_email", "billing_fname", "billing_lname", "billing_organization", "billing_address1", "billing_address2", "billing_city", 
						"billing_zone", "billing_postal_code", "billing_country", "billing_phone", "billing_fax"  
					);
					foreach($va_billing_fields as $vs_f) {
						$va_info = $t_order->getFieldInfo($vs_f);
						if (($vn_width = $va_info['DISPLAY_WIDTH']) > $vn_max_field_width) { $vn_width = $vn_max_field_width; }
						print $t_order->htmlFormElement($vs_f, null, array('width' => $vn_width, 'field_errors' => $va_errors[$vs_f]));
					}
			?>
						</td>
						<td style='width:20px;'>&nbsp;</td>
						<td>
							<h2>
								<div class="addressOptionCheckbox"><input type="checkbox" name="use_billing_as_shipping" value="1" id="useBillingAsShipping"/> <?php print _t('Use billing address for shipping'); ?></div>
								<?php print _t('Shipping'); ?>
							</h2>
							<div id='caShippingFields'>
			<?php
					$va_shipping_fields = array(
						"shipping_email", "shipping_fname", "shipping_lname", "shipping_organization", "shipping_address1", "shipping_address2", "shipping_city",
						"shipping_zone", "shipping_postal_code", "shipping_country", "shipping_phone", "shipping_fax"
					);
					foreach($va_shipping_fields as $vs_f) {
						$va_info = $t_order->getFieldInfo($vs_f);
						if (($vn_width = $va_info['DISPLAY_WIDTH']) > $vn_max_field_width) { $vn_width = $vn_max_field_width; }
						print $t_order->htmlFormElement($vs_f, null, array('width' => $vn_width, 'field_errors' => $va_errors[$vs_f]));
					}
			?>
							</div>
						</td>
					</tr>
				</table>
			</div><!-- end formContainerBg -->
		</div>	
<?php
		$va_order_fields = array(
			"order_notes"
		);
		foreach($va_order_fields as $vs_f) {
			$va_info = $t_order->getFieldInfo($vs_f);
			if (($vn_width = $va_info['DISPLAY_WIDTH']) > $vn_max_field_width) { $vn_width = $vn_max_field_width; }
			
			$vs_on_change = '';
			switch($vs_f) {
				default:
					$vs_format = null;
					$vs_classname = null;
					$vn_width = "735px";
					break;
			}
			
			print $t_order->htmlFormElement($vs_f, $vs_format, array('classname' => $vs_classname, 'width' => $vn_width, 'field_errors' => $va_errors[$vs_f], 'onchange' => $vs_on_change));
		}
?>
<div id="<?php print $vs_id_prefix.'_item'; ?>">
<?php
	//
	// Template to generate display for existing items
	//
?>
	<textarea class='caItemTemplate' style='display: none;'>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo sortableOrderItem">
			<a href="#" class="caDeleteItemButton" style="float: right;"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
								
			<table>
				<tr>
					<td colspan='2'>
						<div style="font-size: 14px; font-weight: bold;">
							<a href="<?php print urldecode(caEditorUrl($this->request, 'ca_objects', '{object_id}')); ?>" class="caEditItemButton" id="<?php print $vs_id_prefix; ?>_edit_related_{n}">{name} ({idno})</a>
						</div>
					</td>
				</tr>
				<tr>
					<td><?php print $t_order_item->htmlFormElement('loan_checkout_date', null, array('value' => '{loan_checkout_date}', 'dateFormat' => 'delimited', 'timeOmit' => true, 'classname' => 'dateBg', 'value' => '{loan_checkout_date}', 'name' => $vs_id_prefix.'_loan_checkout_date_{n}', 'id' => $vs_id_prefix.'_loan_checkout_date_{n}')); ?></td>
					<td><?php print $t_order_item->htmlFormElement('loan_due_date',  null, array('value' => '{loan_due_date}', 'dateFormat' => 'delimited', 'timeOmit' => true, 'classname' => 'dateBg', 'value' => '{loan_due_date}', 'name' => $vs_id_prefix.'_loan_due_date_{n}', 'id' => $vs_id_prefix.'_loan_due_date_{n}')); ?></td>
				</tr>
<?php
	if ($vb_loan_use_item_fee_and_tax) {
?>
				<tr>
					<td><?php print $t_order_item->htmlFormElement('fee', $vs_currency_input_format, array('classname' => 'currencyBg', 'value' => '{fee}', 'name' => $vs_id_prefix.'_fee_{n}', 'id' => $vs_id_prefix.'_fee_{n}')); ?></td>
					<td><?php print $t_order_item->htmlFormElement('tax',  $vs_currency_input_format, array('classname' => 'currencyBg', 'value' => '{tax}', 'name' => $vs_id_prefix.'_tax_{n}', 'id' => $vs_id_prefix.'_tax_{n}')); ?></td>
				</tr>
<?php
	}
	if ($vb_loan_use_notes_and_restrictions) {
?>
				<tr>
					<td width='260'><?php print str_replace('textarea', 'textentry', $t_order_item->htmlFormElement('notes', null, array('value' => '{notes}', 'name' => $vs_id_prefix.'_notes_{n}', 'id' => $vs_id_prefix.'_notes_{n}', 'width' => '250px'))); ?></td>
					<td width='330'><?php print str_replace('textarea', 'textentry', $t_order_item->htmlFormElement('restrictions',  null, array('value' => '{restrictions}', 'name' => $vs_id_prefix.'_restrictions_{n}', 'id' => $vs_id_prefix.'_restrictions_{n}', 'width' => '250px'))); ?></td>
				</tr>
<?php
	}
	if ($vb_loan_use_additional_fees && ($vs_additional_fees = $this->getVar('additional_fees'))) {
?>
				<tr>
					<td colspan="2">
						<?php print $vs_additional_fees; ?>
					</td>
				</tr>
<?php
	}
?>
			</table>
			
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}"/>
			
		</div>
	</textarea>
<?php
	//
	// Template to generate controls for creating new item
	//
?>
	<textarea class='caNewItemTemplate' style='display: none;'>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
		<div id="<?php print $vs_id_prefix; ?>Item_{n}" class="labelInfo">
			<a href="#" class="caDeleteItemButton" style="float: right;"><?php print caNavIcon($this->request, __CA_NAV_BUTTON_DEL_BUNDLE__); ?></a>
			
			<span class="formLabelError">{error}</span>
			<table class="caListItem">
				<tr>
					<td><div class="formLabel"><?php print _t('Item'); ?></div></td>
					<td colspan='2'>
						<div class="formLabel">
							<input type="text" size="100" name="<?php print $vs_id_prefix; ?>_autocomplete{n}" value="{autocomplete}" id="<?php print $vs_id_prefix; ?>_autocomplete{n}" class="lookupBg"/>
						</div>
					</td>
				</tr>
				<tr>
					<td> </td>
					<td><?php print $t_order_item->htmlFormElement('loan_checkout_date', null, array('value' => '{loan_checkout_date}', 'dateFormat' => 'delimited', 'timeOmit' => true, 'classname' => 'dateBg', 'name' => $vs_id_prefix.'_loan_checkout_date_{n}', 'id' => $vs_id_prefix.'_loan_checkout_date_{n}')); ?></td>
					<td><?php print $t_order_item->htmlFormElement('loan_due_date',  null, array('value' => '{loan_due_date}', 'dateFormat' => 'delimited', 'timeOmit' => true, 'classname' => 'dateBg', 'name' => $vs_id_prefix.'_loan_due_date_{n}', 'id' => $vs_id_prefix.'_loan_due_date_{n}')); ?></td>
				</tr>
<?php
	if ($vb_loan_use_item_fee_and_tax) {
?>
				<tr>
					<td> </td>
					<td><?php print $t_order_item->htmlFormElement('fee', $vs_currency_input_format, array('classname' => 'currencyBg', 'value' => '{fee}', 'name' => $vs_id_prefix.'_fee_{n}', 'id' => $vs_id_prefix.'_fee_{n}')); ?></td>
					<td><?php print $t_order_item->htmlFormElement('tax',  $vs_currency_input_format, array('classname' => 'currencyBg', 'value' => '{tax}', 'name' => $vs_id_prefix.'_tax_{n}', 'id' => $vs_id_prefix.'_tax_{n}')); ?></td>
				</tr>
<?php
	}
	if ($vb_loan_use_notes_and_restrictions) {
?>
				<tr>
					<td> </td>
					<td><?php print str_replace('textarea', 'textentry', $t_order_item->htmlFormElement('notes', null, array('value' => '{notes}', 'name' => $vs_id_prefix.'_notes_{n}', 'id' => $vs_id_prefix.'_notes_{n}'))); ?></td>
					<td><?php print str_replace('textarea', 'textentry', $t_order_item->htmlFormElement('restrictions',  null, array('value' => '{restrictions}', 'name' => $vs_id_prefix.'_restrictions_{n}', 'id' => $vs_id_prefix.'_restrictions_{n}'))); ?></td>
				</tr>
<?php
	}
	if ($vb_loan_use_additional_fees && ($vs_additional_fees = $this->getVar('additional_fees_for_new_items'))) {
?>
				<tr>
					<td> </td>
					<td colspan="2">
						<?php print $vs_additional_fees; ?>
					</td>
				</tr>
<?php
	}
?>
			</table>
			<input type="hidden" name="<?php print $vs_id_prefix; ?>_id{n}" id="<?php print $vs_id_prefix; ?>_id{n}" value="{id}" class="caCheckoutItemID"/>
		</div>
		<script type="text/javascript"><?php
			//print 'caGetDefaultFee("'.$vs_id_prefix.'_service_{n}", "'.$vs_id_prefix.'_fee_{n}", "{n}")';
?>
		</script>
	</textarea>
	
	<div class="bundleContainer">
		<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon($this->request, __CA_NAV_BUTTON_ADD__); ?> <?php print _t("Add item to order"); ?></a></div>
		<div class="caItemList">
		
		</div>
		<input type="hidden" name="<?php print $vs_id_prefix; ?>BundleList" id="<?php print $vs_id_prefix; ?>BundleList" value=""/>
		<div style="clear: both; width: 1px; height: 1px;"><!-- empty --></div>
	</div>
</div>
<div class="editorBottomPadding"><!-- empty --></div>
<?php

	print $t_order->htmlFormElement('order_id');
?>
	</form>
</div>
<div class="editorBottomPadding"><!-- empty --></div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#client_autocomplete').autocomplete( 
			{ 
				source: '<?php print caNavUrl($this->request, 'lookup', 'User', 'Get', array('max' => 100, 'inlineCreate' => 1, 'quickadd' => 1)); ?>',
				minLength: 3, delay: 800, html: true,
				select: function(event, ui) {
					var item_id = ui.item.id;
					if (!parseInt(item_id)) {
						// Create new user
						jQuery('#caClientLibraryCustomerInfoMoreButton').css('display', 'inline').click();
						jQuery('#transaction_user_id').val(0);
						jQuery('#client_autocomplete').val('');
						event.preventDefault();
				
						var lname = ui.item.fname;
						var fname = ui.item.lname;
						jQuery('#caBillingFields input[name=billing_fname]').val(fname);
						jQuery('#caBillingFields input[name=billing_lname]').val(lname);
					} else {
						// Set existing user and get address info from server
						jQuery('#transaction_user_id').val(item_id);
						jQuery('#caClientLibraryCustomerInfoMoreButton').css('display', 'inline');
						jQuery.getJSON('<?php print caNavUrl($this->request, "client/orders", "OrderEditor", "GetUserProfileInfo"); ?>', {user_id: item_id}, function(data) {
							var n, k;
							for(k in data) {
								switch(k) {
									case 'state':
										n = 'zone_text';
										break;
									case 'postalcode':
										n = 'postal_code';
										break;
									case 'country':
										jQuery('#billing_zone_select').val(data['state']);
										jQuery('#shipping_zone_select').val(data['state']);
										break;
									default:
										n = k;
										break;
								}
								jQuery('#caBillingFields input[name=billing_' + n + ']').val(data[k]);
								jQuery('#caShippingFields input[name=shipping_' + n + ']').val(data[k]);
							}
						});
						
						if(ui.item) {
							jQuery('#caBillingFields input[name=billing_fname]').val(ui.item.fname);
							jQuery('#caBillingFields input[name=billing_lname]').val(ui.item.lname);
							jQuery('#caBillingFields input[name=billing_email]').val(ui.item.email);
					
							jQuery('#caShippingFields input[name=shipping_fname]').val(ui.item.fname);
							jQuery('#caShippingFields input[name=shipping_lname]').val(ui.item.lname);
							jQuery('#caShippingFields input[name=shipping_email]').val(ui.item.email);
						}
					}
				}
			}
		).click(function() { this.select(); });
	});
	
	function caUseBillingAddressForShipping(setFields) {		
		if (setFields) {
			jQuery('#caBillingFields input').keyup(function() {
				caSettingShippingFields();
			});
			
			jQuery('#caBillingFields select').change(function() {
				caSettingShippingFields();
			});
			jQuery('#caShippingFields input').attr('readonly', true);
			caSettingShippingFields();
			caUI.utils.updateStateProvinceForCountry({data: {mirrorCountryID: 'shipping_country', mirrorStateProvID: 'shipping_zone', countryID: 'billing_country', stateProvID: 'billing_zone', value: '', statesByCountryList: caStatesByCountryList}});
		} else {
			jQuery('#caBillingFields input').unbind('keyup');
			jQuery('#caBillingFields select').unbind('change');
			jQuery('#caShippingFields input').attr('readonly', false);
		}
	}
	
	function caSettingShippingFields() {
		var billing_fields = [<?php print join(",", caQuoteList($va_billing_fields)); ?>];
		var shipping_fields = [<?php print join(",", caQuoteList($va_shipping_fields)); ?>];
		
		for(var i=0; i < billing_fields.length; i++) {
			jQuery('input#' + shipping_fields[i]).val(jQuery('input#' + billing_fields[i]).val());
			jQuery('input#' + shipping_fields[i] + '_text').val(jQuery('input#' + billing_fields[i] + '_text').val());
			jQuery('select#' + shipping_fields[i]).attr('selectedIndex', jQuery('select#' + billing_fields[i]).attr('selectedIndex'));
			jQuery('select#' + shipping_fields[i] + '_select').attr('selectedIndex', jQuery('select#' + billing_fields[i] + '_select').attr('selectedIndex'));
		}
		jQuery('#billing_country').click({mirrorCountryID: 'shipping_country', mirrorStateProvID: 'shipping_zone', countryID: 'billing_country', stateProvID: 'billing_zone', value: '', statesByCountryList: caStatesByCountryList}, caUI.utils.updateStateProvinceForCountry);		
	}
	
	jQuery(document).ready(function() {
		jQuery('#useBillingAsShipping').click(
			function() {
				caUseBillingAddressForShipping((jQuery('#useBillingAsShipping').attr('checked')));
			}
		);
	});
	
	var caRelationBundle<?php print $vs_id_prefix; ?>;
	jQuery(document).ready(function() {
		caRelationBundle<?php print $vs_id_prefix; ?> = caUI.initRelationBundle('#<?php print $vs_id_prefix.'_item'; ?>', {
			fieldNamePrefix: '<?php print $vs_id_prefix; ?>_',
			templateValues: ['_display', 'id', 'object_id', 'item_id', 'name', 'name_sort', 'idno', 'idno_sort', 'loan_checkout_date', 'loan_due_date', 'fee', 'tax', 'notes', 'restrictions', 'thumbnail_tag', 'autocomplete', 'representation_count'<?php print (sizeof($va_additional_fee_template_codes)) ? ", ".join(", ", $va_additional_fee_template_codes) : ""; ?>],
			initialValues: <?php print json_encode($va_initial_values); ?>,
			forceNewValues: <?php print json_encode($va_failed_inserts); ?>,
			defaultValues: <?php print json_encode($va_default_values); ?>,
			itemID: '<?php print $vs_id_prefix; ?>Item_',
			templateClassName: 'caNewItemTemplate',
			initialValueTemplateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showEmptyFormsOnLoad: 1,
			autocompleteUrl: '<?php print caNavUrl($this->request, 'client/library', 'CheckOut', 'Get'); ?>',
			isSortable: true,
			listSortOrderID: '<?php print $vs_id_prefix; ?>BundleList',
			listSortItems: 'div.sortableOrderItem',
			addMode: 'prepend',
			onItemCreate: function() {
				jQuery('#<?php print $vs_id_prefix.'_item'; ?> .dateBg').datepicker();
			},
			autocompleteOptions: {
				onSelect: function(autocompleter_id, data) {
					if (data[1] == 0) {
						jQuery('#' + autocompleter_id).val('');
						return false;	
					}
					if(data[3] > 0) {
						jQuery('#' + autocompleter_id).val('');
						var msg = '<?php print addslashes(_t("<em>%1</em> is currently on loan and is due to be returned on %2")); ?>';
						msg = msg.replace("%1", data[0]);
						msg = msg.replace("%2", data[4]);
						jQuery.jGrowl(msg, { sticky: false, speed:'fast' });
						return false;
					}
					return true;
				}
			}
		});
	});
	
	var caDefaultFees = <?php print json_encode($this->getVar('default_item_prices')); ?>;
	function caGetDefaultFee(serviceDOMID, feeDOMID, n) {
			var v= jQuery("#" + serviceDOMID).val();
			
			var value;
			if (!(value = caDefaultFees[v])) { value = caDefaultFees['__default__']['base']; }
			jQuery("#" + feeDOMID).val(caDefaultFees[v]['base']);
	}
	
	jQuery("#caClientLibraryCheckoutForm").submit(function() {
		if (!jQuery('#transaction_user_id').val()) {  // check if client has been selected
			jQuery.jGrowl('<?php print htmlspecialchars(_t("Please select a client", ENT_QUOTES, "UTF-8")); ?>', { sticky: false, speed:'fast' });
			return false;
		}
		
		if ((parseInt(jQuery('#transaction_user_id').val()) == 0) && (!jQuery('#billing_email').val())) {  // check if new client email has been set
			jQuery.jGrowl('<?php print htmlspecialchars(_t("Please specify a billing email address for the client. This will be used as the login name for their newly created account."), ENT_QUOTES, "UTF-8"); ?>', { sticky: false, speed:'fast' });
			return false;
		}
		
		var hasItems = false;
		jQuery.each(jQuery('input.caCheckoutItemID'), function(k,v) {
			if (jQuery(v).val() > 0) { hasItems = true; return false; }
		});
		
		if (!hasItems) {
			jQuery.jGrowl('<?php print htmlspecialchars(_t("Please add at least one item to checkout"), ENT_QUOTES, "UTF-8"); ?>', { sticky: false, speed:'fast' });
			return false;
		}
		
		// TODO: check dates of items
		return true;
	});
</script>