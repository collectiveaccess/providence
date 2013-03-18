<?php
/* ----------------------------------------------------------------------
 * app/views/client/library/order_overview_html.php : 
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
 
 			JavascriptLoadManager::register('tableList');
 			
	$t_order = $this->getVar('t_order');
	$t_order->set('order_type', 'L');
	$vn_order_id = (int)$t_order->getPrimaryKey();
	
	$t_transaction = $this->getVar('t_transaction');
	$vn_transaction_id = $this->getVar('transaction_id');
	
	$vs_currency_symbol = $this->getVar('currency_symbol');
	$va_errors = $this->getVar('errors');
	
	$vn_max_field_width = 50;
	
	$va_items = $t_order->getItems();
	$va_order_totals = $t_order->getOrderTotals();
	$va_item_counts_by_fulfillment_method = $t_order->getFulfillmentItemCounts();
	
	print caFormTag($this->request, 'SaveOrderOverview', 'caClientOrderOverviewForm', null, 'post', 'multipart/form-data', '_top', array());

	
	$va_users_other_orders = $t_order->getOrders(array('user_id' => $vn_client_user_id = $t_order->getOrderTransactionUserID(), 'type' => 'L', 'is_outstanding' => true, 'exclude' => array($vn_order_id)));
?>
<h1><?php print _t('Loan Overview'); ?></h1>
<div id="caClientOrderOverview">
	
	
	<div class="orderStatus">
<?php
	// status
	$vs_order_status = $t_order->get('order_status');
	$vs_order_status_display = "<em>".$t_order->getChoiceListValue('order_status', $vs_order_status)."</em>";
	$vs_order_status_description = '';
	$vs_next_step = '';
	switch($vs_order_status) {
		case 'OPEN':
			$vs_status_message = _t('Status: %1', $vs_order_status_display);
			TooltipManager::add("#commerceOrderStatusMessage", $vs_order_status_description = _t('Loan request is being entered by the client and not yet submitted for processing.'));
			break;
		case 'SUBMITTED':
			$vs_status_message = _t('Status: %1', $vs_order_status_display);
			TooltipManager::add("#commerceOrderStatusMessage", $vs_order_status_description = _t('Loan request has been submitted by the client for processing. Please review the request, modify pricing as required, assemble items to be loaned and then click the "process" button below.'));
			$vs_next_step = caNavLink($this->request, _t('Process')." &rsaquo;", 'caClientOrderOverviewButton',  'client/library', 'OrderEditor', 'ReturnQuoteToUser', array('order_id' => $vn_order_id));
			break;
		case 'AWAITING_PAYMENT':
			$vs_status_message = _t('Status: %1', $vs_order_status_display);
			TooltipManager::add("#commerceOrderStatusMessage", $vs_order_status_description = _t('Loan request requires payment by client. If you have payment details to enter for the client click on the "enter payment information" button below. Otherwise you must wait for the client to enter their payment information via the client interface.'));
			$vs_next_step = caNavLink($this->request, _t('Enter payment information')." &rsaquo;", 'caClientOrderOverviewButton',  'client/library', 'OrderEditor', 'Payment', array('order_id' => $vn_order_id));
			
			break;
		case 'PROCESSED':
			$vs_status_message = _t('Status: %1', $vs_order_status_display);
			
			if ($t_order->requiresShipping()) {
				TooltipManager::add("#commerceOrderStatusMessage", $vs_order_status_description = _t('Loan has been made. If the load requires shipping click on the "record shipment details" below to enter shipping information.'));
				$vs_next_step = caNavLink($this->request, _t('Record shipment details')." &rsaquo;", 'caClientOrderOverviewButton',  'client/library', 'OrderEditor', 'Shipping', array('order_id' => $vn_order_id));
			} else {
				TooltipManager::add("#commerceOrderStatusMessage", $vs_order_status_description = _t('Loan has been made. It will be closed automatically when the last item is returned.'));
			}
			break;
		case 'COMPLETED':
			$vs_status_message = _t('Status: %1', $vs_order_status_display);
			TooltipManager::add("#commerceOrderStatusMessage", $vs_order_status_description = _t('Loan is complete. No further action is required.'));
			
			break;
		default:
			$vs_status_message = _t("Invalid status %1", $vs_order_status);
			$vs_next_step = '';
			break;
	}

	print "<h1 id='commerceOrderStatusMessage'>".$vs_status_message."</h1>";
?>
<div class="overrideButton" style='float:right;'><a href="#" class="button" onclick="jQuery('#caCommerceOrderStatusOverride').toggle(150);"><?php print _t('Change loan status'); ?> &rsaquo;</a></div>
<?php
	print "<div class='statusDesc'>{$vs_order_status_description}\n";
?>

	
	<div class="formContainerBg" id="caCommerceOrderStatusOverride" style="display: none;">
	<?php
		print "<div class='formLabel'>";
		print $t_order->htmlFormElement('order_status', "^LABEL ^ELEMENT", array('width' => $vn_width));
		
		print caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'caClientOrderOverviewForm');
		print "</div>\n";
	?>
	</div><!-- end formContainerBg -->
<?php	

	
		
	//
	// Display warnings for things that are probably mistakes in the order
	//
	$va_warnings = array();
	if ($t_order->requiresShipping()) {
		
		// Shipping required but no method set
		if ($t_order->get('shipping_method') == 'NONE') {
			$va_warnings[] = _t('Action Required: loan requires shipping but no shipping method is set!');
		}
		
		// Missing shipping address
		if (!$t_order->get('shipping_lname') || !$t_order->get('shipping_address1') || !$t_order->get('shipping_city') || !$t_order->get('shipping_zone') || !$t_order->get('shipping_country') || !$t_order->get('shipping_postal_code')) {
			$va_warnings[] = _t('Action Required: shipping address is incomplete!');
		}
		
		switch($t_order->get('order_status')) {
			case 'PROCESSED':
			case 'PROCESSED_AWAITING_DIGITIZATION':	
			case 'PROCESSED_AWAITING_MEDIA_ACCESS':
				if (!$t_order->get('shipped_on_date') && !$t_order->get('ship_date')) { 			// Order paid for but not shipped or estimate ship date set
					$va_warnings[] = _t('Action Required: loan requires shipping!');
				} 
				break;
			case 'COMPLETED':	
				if (!$t_order->get('shipped_on_date') ) { 			// Order complete but not shipped
					$va_warnings[] = _t('Action Required: loan is complete but items never shipped!');
				} 
				break;
		}
		
	}
	
	// payment
	switch($t_order->get('order_status')) {
		case 'PROCESSED':	
			if (!$t_order->get('payment_received_on') && ($t_order->getTotal() > 0)) { 			// no payment date for processed order?
				$va_warnings[] = _t('Action Required: no payment recorded!');
			} 
			break;
	}
	foreach($va_warnings as $vs_warning){
		print "<div class='statusWarning'>".$vs_warning."</div>";
	}
	print "</div><!-- end statusDesc -->";
	if ($vs_next_step) { print "<h2 style='float: right; text-align:right;'>"._t("Next step").": $vs_next_step</h2>\n"; }
?>
	<div style='width:100%; height:1px; clear:both;'></div>
	</div><!-- end orderStatus -->
<?php
	$va_due_dates = $t_order->getLoanDueDates();
	$va_return_dates = $t_order->getLoanReturnDates();
?>

	<table cellspacing="0" id='loanSummaryTable'>
		<tr>
			<td bgcolor="#f1f1f1"><span class='loanTitle'><?php print _t('Loan Number').": </span></td><td>".$t_order->getOrderNumber()."";?></td>
			<td bgcolor="#f1f1f1"><span class='loanTitle'><?php print _t('Loaned To').": </span></td><td>".$t_order->get('billing_fname').' '.$t_order->get('billing_lname'); ?></td>
		</tr>
		<tr>
			<td bgcolor="#f1f1f1"><span class='loanTitle'><?php print _t('Loan Date').": </span></td><td>".$t_order->get('created_on')."";?></td>
			 
<?php					
				print "<td bgcolor='#f1f1f1'><span class='loanTitle'>"._t('Items Due').": </span></td><td>";
				if (is_array($va_due_dates)) {
					print $va_due_dates['range'];
				}
				print "</td>";			
?>
		</tr>
<?php
			
				print "<tr><td bgcolor='#f1f1f1'><span class='loanTitle'>"._t("Total Cost")."</span></td><td>".$vs_currency_symbol.$va_order_totals['sum']."</td><td bgcolor='#f1f1f1'><span class='loanTitle'>"._t("Items returned").":</span></td><td> ".$va_return_dates['range']."</td></tr>"; 
			
?>
			
	</table>
	
	<?php # print _t('Client loan #%1 – %2 – from %3', $t_order->getOrderNumber(), $t_order->get('created_on'), $t_order->get('billing_fname').' '.$t_order->get('billing_lname')); 

	// messages
	$va_messages = $t_transaction->getMessages();
	$vn_num_messages = sizeof($va_messages);
	$vs_communication_url = caNavUrl($this->request, 'client/library', 'Communications', 'Index', array('transaction_id' => $vn_transaction_id));
?>
	<h3><?php print ($vn_num_messages == 1) ? _t("There has been <a href='%2'>%1 communication</a> regarding this loan request", $vn_num_messages, $vs_communication_url) :  _t("There have been <a href='%2'>%1 communications</a> regarding this loan request", $vn_num_messages, $vs_communication_url); ?></h3>

<?php
	if (is_array($va_users_other_orders) && ($vn_num_other_orders = sizeof($va_users_other_orders))) {
		$vs_other_order_url = caNavUrl($this->request, 'client/library', 'List', 'Index', array('user_id' => $vn_client_user_id));
?>
	<h3><?php print ($vn_num_other_orders == 1) ? _t("Client has <a href='%2'>%1 other outstanding loan</a>", $vn_num_other_orders, $vs_other_order_url) :  _t("Client has <a href='%2'>%1 other outstanding loans</a>", $vn_num_other_orders, $vs_other_order_url); ?></h3>
<?php
	}
?>
	<div class="overviewItem">
<?php 
		$va_output = array();
		foreach(array(
			'item cost' => 'fee', 'item fees' => 'additional_item_fees', 'order fees' => 'additional_order_fees',
			'shipping' => 'shipping', 'handling' => 'handling', 'taxes' => 'tax'
		) as $vs_label => $vs_key) {
			if ($va_order_totals[$vs_key] > 0) {
				$va_output[] = _t("%1 {$vs_label}", $vs_currency_symbol.$va_order_totals[$vs_key]);
			}
		}
		if (sizeof($va_output)) {
			print "<b>"._t("Cost Breakdown").":</b> ".join(" + ", $va_output);
		}
?></div>

	<div >
		<?php print "<h1>".((($vn_item_count = sizeof($va_items)) != 1) ? _t("%1 Items", $vn_item_count) : _t("%1 Item", $vn_item_count))." <span style='font-size:.65em;'>".caNavLink($this->request, _t('Manage'), '', 'client/library', 'OrderEditor', 'ItemList', array('order_id' => $vn_order_id))."</span> <span style='font-size:.65em;'>".caNavLink($this->request, _t('Print checklist'), '', 'client/library', 'OrderEditor', 'GetCheckList', array('order_id' => $vn_order_id))."</span></h1>"; ?>
	</div>
	
	<script language="JavaScript" type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('#caLoanList').caFormatListTable();
		});
	</script>
<?php
print "<table id='caLoanList' class='listtable' width='100%' border='0' cellpadding='0' cellspacing='0'>";
print "<thead><tr style='font-weight:bold; text-transform: uppercase; background-color: #f1f1f1;'><th width='200' class='list-header-unsorted'>"._t('Item Name')."</th><th class='list-header-unsorted'>"._t('ID')."</th><th class='list-header-unsorted'>"._t('Checkout Date')."</th><th class='list-header-unsorted'>"._t('Due Date')."</th><th class='list-header-unsorted'>"._t('Returned')."</th></tr></thead>";
$va_outstanding = 0;
$va_overdue = 0;
print "<tbody>\n";
foreach ($va_items as $va_item) {
	$vn_object_id = $va_item['object_id'];
	print "<tr>";
	
	#print "<td>".$va_item['thumbnail_tag']."</td>"; 
	print "<td class='itemTitle{$vn_object_id}'>".caNavLink($this->request, $va_item['name'], "", "editor", "objects/ObjectEditor", "Edit", array('object_id' => $va_item['object_id']))."</td>";
	print "<td>".$va_item['idno']."</td>";
	print "<td>".date( 'M j, Y', $va_item['loan_checkout_date'])."</td>";
	if (($va_item['loan_due_date'] < time()) && (!$va_item['loan_return_date'])) {
		print "<td style='color: red; font-weight:bold;'>";
		$va_overdue++;
	} else {
		print "<td>";
	}
	if (!$va_item['loan_return_date']) {$va_outstanding++;}
	print date( 'M j, Y', $va_item['loan_due_date'])."</td>";
	print "<td>";
		if ($va_item['loan_return_date']) {
			print date( 'M j, Y', $va_item['loan_return_date']);
		}
	print "</td>";
	print "</tr>";
	TooltipManager::add(
		".itemTitle{$vn_object_id}", "<table><tr><td>".$va_item['thumbnail_tag']."</td><td><b>".$va_item['idno']."<br/><br/></b>".$va_item['name']."</td></tr></table>"
	);
}
print "</tbody>\n";
print "</table>";

if ($va_outstanding != 0) {
	print "<div class='orderTotal'>".$va_outstanding." "._t('items still in circulation')."</div>";
} else {
	print "<div class='orderTotal'>"._t('All items have been returned')."</div>";
}
if ($va_overdue != 0) {
	print "<div class='orderTotal overdue'>".$va_overdue." "._t('items overdue')."</div>";
}
print "<div style='height:70px; width: 100%; clear:both;'></div>";
	// shipping
	if ($t_order->requiresShipping()) {
		$va_shipping_name_list = array();
		foreach(array('shipping_city', 'shipping_zone', 'shipping_country') as $vs_shipping_field) {
			if ($vs_tmp = $t_order->get($vs_shipping_field)) { $va_shipping_name_list[] = $vs_tmp; }
		}
		$vs_shipping_destination = sizeof($va_shipping_name_list) ? join(", ", $va_shipping_name_list) : "?";
		$vs_shipping_method = $t_order->get('shipping_method');
		
		if ($vs_shipped_on_date = $t_order->get('shipped_on_date', array('timeOmit' => true))) {
			// order has already been shipped
?>
	<div class="overviewItem"><?php print _t("%1 items were <a href='%5'>shipped</a> to %2 via %3 on %4", $vn_item_count, $vs_shipping_destination, $vs_shipping_method, $vs_shipped_on_date, caNavUrl($this->request, 'client/library', 'OrderEditor', 'Shipping', array('order_id' => $vn_order_id))); ?></div>
<?php			
		} else {
			if ($vs_ship_date = $t_order->get('shipping_date', array('timeOmit' => true))) {
				// order has planned shipping date
?>
	<div class="overviewItem"><?php print _t("%1 items planned <a href='%5'>for shipment</a> to %2 via %3 is on %4", $va_item_counts_by_fulfillment_method['SHIPMENT'], $vs_shipping_destination, $vs_shipping_method, $vs_ship_date, caNavUrl($this->request, 'client/library', 'OrderEditor', 'Shipping', array('order_id' => $vn_order_id))); ?></div>
<?php
			} else {
				if (in_array($t_order->get('order_status'), array('PROCESSED', 'PROCESSED_AWAITING_DIGITIZATION', 'PROCESSED_AWAITING_MEDIA_ACCESS'))) {
					// needs to be shipped now
?>
	<div class="overviewItem"><?php print _t("%1 items require <a href='%4'>shipping</a> to %2 via %3", $va_item_counts_by_fulfillment_method['SHIPMENT'], $vs_shipping_destination, $vs_shipping_method, caNavUrl($this->request, 'client/library', 'OrderEditor', 'Shipping', array('order_id' => $vn_order_id))); ?></div>
<?php		
				} else {
					// will need to be shipped after payment
?>
	<div class="overviewItem"><?php print _t("%1 items will require <a href='%4'>shipping</a> to %2 via %3 when paid for", $va_item_counts_by_fulfillment_method['SHIPMENT'], $vs_shipping_destination, $vs_shipping_method, caNavUrl($this->request, 'client/library', 'OrderEditor', 'Shipping', array('order_id' => $vn_order_id))); ?></div>
<?php			
				}	
			}
		}

	}


	// Order_id used when saving "change status" form	
	print $t_order->htmlFormElement('order_id');

?>
</form>
</div><!-- end caClientOrderOverview -->