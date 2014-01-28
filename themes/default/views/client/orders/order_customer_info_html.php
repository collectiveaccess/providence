<?php
/* ----------------------------------------------------------------------
 * app/views/client/order_customer_info_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
	$t_order = $this->getVar('t_order');
	$vn_order_id = (int)$t_order->getPrimaryKey();
	$vn_transaction_id = $this->getVar('transaction_id');
	$va_errors = $this->getVar('errors');
	
	$vn_max_field_width = 50;
	
	print $vs_control_box = caFormControlBox(
		(caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'caClientOrderCustomerForm')).' '.
		(caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'client/orders', 'OrderEditor', 'CustomerInfo', array('order_id' => $vn_order_id))),
		'',
		(caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'client/orders', 'OrderEditor', 'Delete', array('order_id' => $vn_order_id)))
	);
	
	print caFormTag($this->request, 'SaveCustomerInfo', 'caClientOrderCustomerForm', null, 'post', 'multipart/form-data', '_top', array());
	
	if (!$vn_transaction_id) {
?>
	<h1><?php print _t('User account to associate order with'); ?></h1>
	<div class="formLabel">
		<?php print _t('User account'); ?>:
		<input type="text" size="60" name="client_autocomplete" value="" id="client_autocomplete" class="lookupBg"/>
		<input type="hidden" name="transaction_user_id" id="transaction_user_id" value=""/>
	</div>
	
	<script type="text/javascript">
		jQuery('#client_autocomplete').autocomplete( 
			{ 
				minLength: 3, delay: 800,
				source: '<?php print caNavUrl($this->request, 'lookup', 'User', 'Get', array('max' => 100)); ?>',
				select: function(event, ui) {
					var user_id = ui.item.id;
					jQuery('#transaction_user_id').val(user_id);
					if(user_id) {
						jQuery('#caBillingFields input[name=billing_fname]').val(ui.item.fname);
						jQuery('#caBillingFields input[name=billing_lname]').val(ui.item.lname);
						jQuery('#caBillingFields input[name=billing_email]').val(ui.item.email);
				
						jQuery('#caShippingFields input[name=shipping_fname]').val(ui.item.fname);
						jQuery('#caShippingFields input[name=shipping_lname]').val(ui.item.lname);
						jQuery('#caShippingFields input[name=shipping_email]').val(ui.item.email);
				
						// grab the user's profile information
						jQuery.getJSON('<?php print caNavUrl($this->request, 'client/orders', 'OrderEditor', 'GetUserProfileInfo'); ?>', { user_id: user_id }, function(d, t, x) { 
							jQuery('#caBillingFields input[name=billing_organization]').val(d['organization']);
							jQuery('#caBillingFields input[name=billing_address1]').val(d['address1']);
							jQuery('#caBillingFields input[name=billing_address2]').val(d['address2']);
							jQuery('#caBillingFields input[name=billing_city]').val(d['city']);
							jQuery('#caBillingFields input[name=billing_postal_code]').val(d['postalcode']);
							jQuery('#caBillingFields input[name=billing_phone]').val(d['phone']);
							jQuery('#caBillingFields input[name=billing_fax]').val(d['fax']);	//
					
							jQuery('#caBillingFields select[name=billing_country]').val(d['country']);
							jQuery('#billing_country').click();
					
							jQuery('#caBillingFields #billing_zone_select').val(d['state']);
							jQuery('#caBillingFields #billing_zone_text').val(d['state']);
					
							jQuery('#caShippingFields input[name=shipping_organization]').val(d['organization']);
							jQuery('#caShippingFields input[name=shipping_address1]').val(d['address1']);
							jQuery('#caShippingFields input[name=shipping_address2]').val(d['address2']);
							jQuery('#caShippingFields input[name=shipping_city]').val(d['city']);
							jQuery('#caShippingFields input[name=shipping_postal_code]').val(d['postalcode']);
							jQuery('#caShippingFields input[name=shipping_phone]').val(d['phone']);
							jQuery('#caShippingFields input[name=shipping_fax]').val(d['fax']);	//
					
							jQuery('#caShippingFields select[name=shipping_country]').val(d['country']);
							jQuery('#shipping_country').click();
					
							jQuery('#caShippingFields #shipping_zone_select').val(d['state']);
							jQuery('#caShippingFields #shipping_zone_text').val(d['state']);
						});
					}
				}
			}
		);
	</script>
<?php
	} else {
		if (!$vn_order_id) {
			print caHTMLHiddenInput('transaction_id', array('value' => $vn_transaction_id));
		}
	}
?>
	<h2><?php print _t('Customer information'); ?></h2>
	<div class="formContainerBg" style="padding-top:0px;">
		<table width="100%">
			<tr>
				<td>
					<h2><?php print _t('Billing'); ?></h2>
					<div id='caBillingFields'>
	<?php
			$va_billing_fields = array(
				"billing_fname", "billing_lname", "billing_organization", "billing_address1", "billing_address2", "billing_city", 
				"billing_zone", "billing_postal_code", "billing_country", "billing_phone", "billing_fax", "billing_email"  
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
				"shipping_fname", "shipping_lname", "shipping_organization", "shipping_address1", "shipping_address2", "shipping_city",
				"shipping_zone", "shipping_postal_code", "shipping_country", "shipping_phone", "shipping_fax", "shipping_email"
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
	
	<h2><?php print _t('Sale information'); ?></h2>
	<div class="formContainerBg" style="padding-top:10px;">
<?php
		foreach(array('sales_agent') as $vs_f) {
			$va_info = $t_order->getFieldInfo($vs_f);
			print $t_order->htmlFormElement($vs_f, null, array('width' => "700px", 'field_errors' => $va_errors[$vs_f]));
		}
?>
	</div>
	
	
<?php
	print $t_order->htmlFormElement('order_id');

	print $vs_control_box;
?>
</form>
<div class="editorBottomPadding"><!-- empty --></div>
<script type="text/javascript">
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
</script>