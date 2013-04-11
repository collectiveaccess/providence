<?php
/* ----------------------------------------------------------------------
 * bundles/ca_commerce_orders.php : 
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
 
	$vs_id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
	$vn_table_num 				= $this->getVar('table_num');
	
	$t_subject					= $this->getVar('t_subject');
	
	$va_settings 				= $this->getVar('settings');
	$vs_order_type				= in_array($va_settings['order_type'][0], array('O', 'L')) ? $va_settings['order_type'][0] : 'O';

	$vb_read_only		=	(isset($va_settings['readonly']) && $va_settings['readonly']);

	$va_errors = array();
	
	$va_orders = $t_subject->getClientHistory($vs_order_type);
	
	print caEditorBundleShowHideControl($this->request, $vs_id_prefix.$vn_table_num.'OrderHistory');
?>
<div id="<?php print $vs_id_prefix.$vn_table_num.'OrderHistory'; ?>">
	<div class="bundleContainer">
<?php
	if ($vs_order_type == 'L') {
		// Loan
		if (sizeof($va_orders) == 0) {
?>
			<h2><?php print _t('No loans have been made'); ?></h2>
<?php
		} else {
?>
		<div class="caItemList">	
			<table class="caClientOrderHistoryTable">
				<theader>
					<th class="caClientOrderHistoryHeader"><?php print _t('Loan #'); ?></th>
					<th class="caClientOrderHistoryHeader"><?php print _t('Loaned to'); ?></th>
					<th class="caClientOrderHistoryHeader"><?php print _t('Checkout'); ?></th>
					<th class="caClientOrderHistoryHeader"><?php print _t('Due'); ?></th>
					<th class="caClientOrderHistoryHeader"><?php print _t('Returned'); ?></th>
					<th class="caClientOrderHistoryHeader"><?php print _t('Notes'); ?></th>
				</theader>
				<tbody>
<?php
			foreach($va_orders as $vn_id => $va_order) {
				$vs_checkout_date = $va_order['loan_checkout_date'];
				$vn_checkout_date = $va_order['loan_checkout_date_raw'];
				$vs_due_date = $va_order['loan_due_date'];
				$vn_due_date = $va_order['loan_due_date_raw'];
				$vs_ret_date = $va_order['loan_return_date'];
				$vn_ret_date = $va_order['loan_return_date_raw'];
				
				if (!$vn_ret_date) {
					if (time() > $vn_due_date) {	// is it overdue?
						$vs_row_class = 'caClientOrderHistoryRowOverdue';
					} else {
						$vs_row_class = 'caClientOrderHistoryRowOut';
					}
				} else {
					$vs_row_class = 'caClientOrderHistoryRow';
				}
?>
				<tr class='<?php print $vs_row_class; ?>'>
					<td class="caClientOrderHistoryText"><?php print caNavLink($this->request, $va_order['order_number'], '', 'client/library', 'OrderEditor', 'Edit',  array('order_id' => $va_order['order_id'])); ?></td>
					<td class="caClientOrderHistoryText"><?php print $va_order['billing_fname'].' '.$va_order['billing_lname']." (".$va_order['billing_email'].")"; ?></td>
					<td class="caClientOrderHistoryText"><?php print $vs_checkout_date; ?></td>
					<td class="caClientOrderHistoryText"><?php print $vs_due_date; ?></td>
					<td class="caClientOrderHistoryText"><?php print $vs_ret_date; ?></td>
					<td class="caClientOrderHistoryText"><?php print $va_order['notes']; ?></td>
				</tr>
<?php 
			}
		}
	} else {
		// Sales order
		if (sizeof($va_orders) == 0) {
?>
			<h2><?php print _t('No orders have been made'); ?></h2>
<?php
		} else {
?>
		<div class="caItemList">	
			<table>
				<theader>
					<th><?php print _t('Order #'); ?></th>
					<th><?php print _t('Ordered by'); ?></th>
					<th><?php print _t('Date'); ?></th>
					<th><?php print _t('Price'); ?></th>
					<th><?php print _t('Status'); ?></th>
				</theader>
				<tbody>
<?php
	
			foreach($va_orders as $vn_id => $va_order) {
				$vs_checkout_date = $va_order['loan_checkout_date'];
?>
				<tr class='caClientOrderHistoryRow'>
					<td class="caClientOrderHistoryText"><?php print caNavLink($this->request, $va_order['order_number'], '', 'client/library', 'OrderEditor', 'Edit',  array('order_id' => $va_order['order_id'])); ?></td>
					<td class="caClientOrderHistoryText"><?php print $va_order['billing_fname'].' '.$va_order['billing_lname']." (".$va_order['billing_email'].")"; ?></td>
					<td class="caClientOrderHistoryText"><?php print $vs_checkout_date; ?></td>
					<td class="caClientOrderHistoryText"><?php print $va_order['fee']; ?></td>
					<td class="caClientOrderHistoryText"><?php print $va_order['order_status']; ?></td>
				</tr>
<?php 
			}
			foreach($va_orders as $vn_id => $va_order) {
?>
				<tr class='caClientOrderHistoryRow'>
					<td><?php print caNavLink($this->request, $va_order['order_number'], '', 'client/orders', 'OrderEditor', 'Edit',  array('order_id' => $va_order['order_id'])); ?></td>
					<td><?php print $va_order['billing_fname'].' '.$va_order['billing_lname']." (".$va_order['billing_email'].")"; ?></td>
					<td><?php print caGetLocalizedDate($va_order['created_on']); ?></td>
					<td><?php print $va_order['fee']; ?></td>
					<td><?php print $va_order['order_status']; ?></td>
				</tr>
<?php 
			}
		}
	}
?>
				</tbody>
			</table>
		</div>
	</div>

</div>