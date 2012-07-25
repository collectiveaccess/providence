<?php
/* ----------------------------------------------------------------------
 * app/views/client/order_additional_fees_html.php : 
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
 
	$t_order = $this->getVar('t_order');
	$vn_order_id = (int)$t_order->getPrimaryKey();
	$vn_transaction_id = $this->getVar('transaction_id');
	$va_errors = $this->getVar('errors');
	
	$vn_max_field_width = 50;
	
	print $vs_control_box = caFormControlBox(
		(caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Save"), 'caClientAdditionalFeesForm')).' '.
		(caNavButton($this->request, __CA_NAV_BUTTON_CANCEL__, _t("Cancel"), 'client/library', 'OrderEditor', 'AdditionalFees', array('order_id' => $vn_order_id))),
		'',
		(caNavButton($this->request, __CA_NAV_BUTTON_DELETE__, _t("Delete"), 'client/library', 'OrderEditor', 'Delete', array('order_id' => $vn_order_id)))
	);
	
	print caFormTag($this->request, 'SaveAdditionalFees', 'caClientAdditionalFeesForm', null, 'post', 'multipart/form-data', '_top', array());

?>
	<h1><?php print _t('Additional loan fees'); ?></h1>
	<div class="formContainerBg" style="padding-top:0px;">
		<table width="100%">
			<tr>
				<td>
					<h2><?php print _t('Fees'); ?></h2>
					<div id='caOrderFeeFields'>
<?php
	print $this->getVar('additional_fees');
?>
					</div>
				</td>
			</tr>
		</table>
	</div>
<?php
	print $t_order->htmlFormElement('order_id');

	print $vs_control_box;
?>
</form>