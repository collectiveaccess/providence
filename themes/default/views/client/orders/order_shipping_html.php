<?php
/* ----------------------------------------------------------------------
 * app/views/client/order_shipping_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2012 Whirl-i-Gig
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
 	
 	JavascriptLoadManager::register('datePickerUI');
 	
	$t_order = $this->getVar('t_order');
	$vn_order_id = (int)$t_order->getPrimaryKey();
	$vn_transaction_id = $this->getVar('transaction_id');
	$va_errors = $this->getVar('errors');
	
	$vs_currency_symbol = $this->getVar('currency_symbol');
	$vs_currency_input_format = "<div class='formLabel'>^LABEL<br/>{$vs_currency_symbol}^ELEMENT</div>";
	
	if ($t_order->requiresShipping()) {
		print $vs_control_box = caFormControlBox(
			(caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'caClientOrderShippingForm')).' '.
			(caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'client/orders', 'OrderEditor', 'Shipping', array('order_id' => $vn_order_id))),
			'', 
			(caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'client/orders', 'OrderEditor', 'Delete', array('order_id' => $vn_order_id)))
		);
	}

	print caFormTag($this->request, 'SaveShipping', 'caClientOrderShippingForm', null, 'post', 'multipart/form-data', '_top', array());
?>
	<h1><?php print _t('Shipping information'); ?></h1>
<?php
	
	if ($t_order->requiresShipping()) {
		$va_shipping_fields = array(
			"shipping_method", "shipping_cost", "handling_cost", "shipping_notes", "shipping_date", "shipped_on_date"
		);
		foreach($va_shipping_fields as $vs_f) {
			if (($vs_f == 'shipped_on_date') && (!in_array($t_order->get('order_status'), array('PROCESSED', 'PROCESSED_AWAITING_DIGITIZATION', 'PROCESSED_AWAITING_MEDIA_ACCESS', 'COMPLETED')))) { continue; }	// don't show shipped on field if order is not paid for
			$va_info = $t_order->getFieldInfo($vs_f);
			if (($vn_width = $va_info['DISPLAY_WIDTH']) > $vn_max_field_width) { $vn_width = $vn_max_field_width; }
			
			$vs_on_change = '';
			switch($vs_f) {
				case 'shipping_cost':
				case 'handling_cost':
					$vs_format = $vs_currency_input_format;
					$vs_classname = 'currencyBg';
					break;
				case 'shipped_on_date':
				case 'shipping_date':
					$vs_format = null;
					$vs_classname = 'dateBg';
					break;
				case 'shipping_method':
					$vs_on_change = "caUpdateFormAvailability()";
					break;
				default:
					$vs_format = null;
					$vs_classname = null;
					break;
			}
			
			print $t_order->htmlFormElement($vs_f, $vs_format, array('classname' => $vs_classname, 'width' => $vn_width, 'field_errors' => $va_errors[$vs_f], 'onchange' => $vs_on_change));
		}
	
		print $t_order->htmlFormElement('order_id');
		
		print $vs_control_box;
	} else {
?>
		<h2><?php print _t('No shipping is required for this order'); ?></h2>
<?php
	}
?>
</form>
<div class="editorBottomPadding"><!-- empty --></div>

<?php
	if ($t_order->requiresShipping()) {
?>
<script type="text/javascript">
	
	function caUpdateFormAvailability() {
		var available = true;
		if (jQuery('#caClientOrderShippingForm #shipping_method').val() == 'NONE') {
			available = false;
		}
		jQuery('#caClientOrderShippingForm input').attr('readonly', !available);
		jQuery('#caClientOrderShippingForm textarea').attr('readonly', !available);
		jQuery('input[name=shipping_date]').attr('disabled', !available);
		jQuery('input[name=shipped_on_date]').attr('disabled', !available);
	}
	jQuery(document).ready(function() {
		jQuery('input[name=shipping_date]').datepicker();
		jQuery('input[name=shipped_on_date]').datepicker();
		caUpdateFormAvailability();
	});
</script>
<?php
	}
?>