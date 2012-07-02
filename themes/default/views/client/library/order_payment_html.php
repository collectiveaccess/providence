<?php
/* ----------------------------------------------------------------------
 * app/views/client/library/order_payment_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
	$o_client_services_config = $this->getVar('client_services_config');
	$va_credit_card_types = $o_client_services_config->getAssoc('credit_card_types');
	if (!is_array($va_payment_info = $t_order->getPaymentInfo())) { $va_payment_info = array(); }

	$vn_order_id = (int)$t_order->getPrimaryKey();
	$vn_transaction_id = $this->getVar('transaction_id');
	$va_errors = $this->getVar('errors');
	
	$vs_currency_symbol = $this->getVar('currency_symbol');
	$vs_currency_input_format = "<div class='formLabel'>^LABEL<br/>{$vs_currency_symbol}^ELEMENT</div>";
	
	print $vs_control_box = caFormControlBox(
		(caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'caClientLoanPaymentForm', array('preventDuplicateSubmits' => true))).' '.
		(caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'client/library', 'OrderEditor', 'Payment', array('order_id' => $vn_order_id))),
		'', 
		(caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'client/library', 'OrderEditor', 'Delete', array('order_id' => $vn_order_id)))
	);
	
	print caFormTag($this->request, 'SavePayment', 'caClientLoanPaymentForm', null, 'post', 'multipart/form-data', '_top', array());

?>
	<h1><?php print _t('Payment information'); ?></h1>
<?php	
	if ($t_order->paymentIsAllowed()) {	
		print $t_order->htmlFormElement('payment_method', $vs_form_element_format, array('width' => $vn_width, 'field_errors' => $va_errors[$vs_f], 'choiceList' => $va_payment_types, 'id' => 'caPaymentMethod'));
?>
		<div style="margin-left: 20px; margin-bottom: 10px;">
<?php
	if (is_array($va_errors['payment_info']) && sizeof($va_errors['payment_info'])) {
?>
	<div class='formLabelError'><?php print join("; ", $va_errors['payment_info']); ?></div>
<?php
	}
?>
			<div id="caClientLoanCustomerCreditSubForm" style="display: none;">
				<table>
<?php
					print "<tr><td>"._t('Credit card')."</td><td>".caHTMLSelect('credit_card_type', $va_credit_card_types, array(), array('value' => $va_payment_info['credit_card_type']))."</td></tr>\n";			
					print "<tr><td>"._t('Credit card number')."</td><td>".caHTMLTextInput('credit_card_number', array('size' => 40, 'value' => $va_payment_info['credit_card_number']))."</td></tr>\n";		
					print "<tr><td>"._t('CCV')."</td><td>".caHTMLTextInput('credit_card_ccv', array('size' => 4, 'value' => $va_payment_info['credit_card_ccv']))."</td></tr>\n";
					print "<tr><td>"._t('Expiration date')."</td><td>".caHTMLSelect('credit_card_exp_mon', $this->getVar('credit_card_exp_month_list'), array(), array('value' => $va_payment_info['credit_card_exp_mon']))." ".caHTMLSelect('credit_card_exp_yr', $this->getVar('credit_card_exp_year_list'), array(), array('value' => $va_payment_info['credit_card_exp_yr']))."</td></tr>\n";	
					
					print "<tr><td> </td><td><div id='caClientLoanProcessingIndicator'><img src='".$this->request->getThemeUrlPath()."/graphics/icons/indicator.gif'/> "._t('Please wait while client loan is processed (this may take up to 60 seconds to complete)')."</div></td></tr>\n";
							
?>
				</table>
			</div>
			<div id="caClientLoanCustomerPOSubForm" style="display: none;">
				<table>
<?php
					print "<tr><td>"._t('Purchase order date')."</td><td>".caHTMLTextInput('purchase_order_date', array('width' => 40, 'class' => 'dateBg', 'value' => $va_payment_info['purchase_order_date']))."</td></tr>\n";
					print "<tr><td>"._t('Purchase order #')."</td><td>".caHTMLTextInput('purchase_order_number', array('width' => 40, 'value' => $va_payment_info['purchase_order_number']))."</td></tr>\n";
?>
				</table>
			</div>
			<div id="caClientLoanCustomerCheckSubForm" style="display: none;">
				<table>
<?php
					print "<tr><td>"._t('Payee')."</td><td>".caHTMLTextInput('check_payee', array('width' => 60, 'value' => $va_payment_info['check_payee']))."</td></tr>\n";
					print "<tr><td>"._t('Bank')."</td><td>".caHTMLTextInput('check_bank', array('width' => 60, 'value' => $va_payment_info['check_bank']))."</td></tr>\n";
					print "<tr><td>"._t('Check date')."</td><td>".caHTMLTextInput('check_date', array('width' => 40, 'class' => 'dateBg', 'value' => $va_payment_info['check_date']))."</td></tr>\n";
					print "<tr><td>"._t('Check #')."</td><td>".caHTMLTextInput('check_number', array('width' => 40, 'value' => $va_payment_info['check_number']))."</td></tr>\n";
?>
				</table>
			</div>
		</div>
		<div id='caPaymentStatusContainer'>
<?php	
			print $t_order->htmlFormElement('payment_received_on', $vs_form_element_format, array('width' => $vn_width, 'field_errors' => $va_errors[$vs_f], 'id' => 'caPaymentDate', 'classname' => 'dateBg'));
			print $t_order->htmlFormElement('payment_status', $vs_form_element_format, array('width' => $vn_width, 'field_errors' => $va_errors[$vs_f], 'id' => 'caPaymentStatus'));
?>
		</div>
<?php	
	} else {
		//
		// Simple read-only type display when user is not allowed to change payment details
		//
		
		if (in_array($t_order->get('payment_status'), array('RECEIVED'))) { 
?>
		<h2><?php print _t('Payment details – fees for this client loan has been paid'); ?></h2>
		<div id='caPaymentFields'>
			<table>
<?php
				print "<tr><td>"._t('Method')."</td><td>".$t_order->getChoiceListValue('payment_method', $vs_payment_method = $t_order->get('payment_method'))."</td></tr>\n";
				print "<tr><td>"._t('Received on')."</td><td>".$t_order->get('payment_received_on')."</td></tr>\n";
				
				$va_payment_info = $t_order->getPaymentInfo();
				switch($vs_payment_method) {
					case 'CREDIT':
						print "<tr><td>"._t('Credit card')."</td><td>".$va_payment_info['credit_card_type']."</td></tr>\n";			
						print "<tr><td>"._t('Credit card number')."</td><td>".$va_payment_info['credit_card_number']."</td></tr>\n";		
						print "<tr><td>"._t('Expiration date')."</td><td>".$va_payment_info['credit_card_exp_mon'].'/'.$va_payment_info['credit_card_exp_yr']."</td></tr>\n";			
						
						$va_payment_response = $t_order->get('payment_response');
						print "<tr><td>"._t('Processor')."</td><td>".$va_payment_response['processor']."</td></tr>\n";	
						print "<tr><td>"._t('TransactionID')."</td><td>".$va_payment_response['transactionID']."</td></tr>\n";	
						break;
					case 'CHECK':
						print "<tr><td>"._t('Payee')."</td><td>".$va_payment_info['check_payee']."</td></tr>\n";
						print "<tr><td>"._t('Bank')."</td><td>".$va_payment_info['check_bank']."</td></tr>\n";
						print "<tr><td>"._t('Check date')."</td><td>".$va_payment_info['check_date']."</td></tr>\n";
						print "<tr><td>"._t('Check #')."</td><td>".$va_payment_info['check_number']."</td></tr>\n";
						break;
					case 'PO':
						print "<tr><td>"._t('Purchase order date')."</td><td>".$va_payment_info['purchase_order_date']."</td></tr>\n";
						print "<tr><td>"._t('Purchase order #')."</td><td>".$va_payment_info['purchase_order_number']."</td></tr>\n";
						break;
				}
?>
			</table>
		</div>
<?php
		} else {
			if ($t_order->getTotal() == 0) {
?>
		<h2><?php print _t('Loan does not require payment'); ?></h2>
<?php			
			} else {
				// order payment details cannot be set yet
?>
		<h2><?php print _t('Loan is not yet ready for payment'); ?></h2>
<?php
			}
		}
	}
?>
	<div class="editorBottomPadding"><!-- empty --></div>
<?php

	print $t_order->htmlFormElement('order_id');

	print $vs_control_box;
?>
</form>
<div class="editorBottomPadding"><!-- empty --></div>

<script type="text/javascript">
	function caSetPaymentFormDisplay(payment_type, speed) {
		if(payment_type == 'CREDIT') {
			jQuery('#caClientLoanCustomerCreditSubForm').slideDown(speed);
			jQuery('#caPaymentStatusContainer').hide();
		} else {
			jQuery('#caClientLoanCustomerCreditSubForm').slideUp(speed);
		}
		if(payment_type == 'PO') {
			jQuery('#caClientLoanCustomerPOSubForm').slideDown(speed);
			jQuery('#caPaymentStatusContainer').show();
		} else {
			jQuery('#caClientLoanCustomerPOSubForm').slideUp(speed);
		}
		if(payment_type == 'CHECK') {
			jQuery('#caClientLoanCustomerCheckSubForm').slideDown(speed);
			jQuery('#caPaymentStatusContainer').show();
		} else {
			jQuery('#caClientLoanCustomerCheckSubForm').slideUp(speed);
		}
		if (payment_type == 'NONE') {
			jQuery('#caPaymentStatusContainer').hide();
		}
	}
	
	jQuery(document).ready(function() {
		jQuery('#caPaymentStatusContainer').hide();
		jQuery('#caPaymentMethod').click(
			function() {
				caSetPaymentFormDisplay(jQuery(this).val(), 250);
			}
		);
		
		caSetPaymentFormDisplay(jQuery('#caPaymentMethod').val(), 0);
		jQuery('input[name=check_date]').datepicker();
		jQuery('input[name=purchase_order_date]').datepicker();
		jQuery('input[name=payment_received_on]').datepicker();
		
		jQuery('#caClientLoanPaymentForm').submit(function() {
			jQuery('#caClientLoanProcessingIndicator').css('display', 'block');
		});
	});
</script>