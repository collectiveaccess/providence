<?php
	$t_order = $this->getVar('t_order');
	$o_client_services_config = caGetClientServicesConfiguration();
	
	$vb_requires_shipping = $t_order->requiresShipping();
	$vb_requires_download = $t_order->requiresDownload();
?>
You were sent the following message by <em><?php print $this->getVar('sender_name'); ?></em> on <em><?php print date('F j, Y g:i a', $this->getVar('sent_on')); ?></em>:

<p>Your order <?php print $t_order->getOrderNumber(); ?> submitted on <?php print date('F j, Y g:i a', (int)$t_order->get('created_on', array('GET_DIRECT_DATE' => true))); ?> has been processed. 
<?php 
	if ($vb_requires_download) {
?>

	Some of your order requires transfer of media to the server. You will be informed by email when your items are ready for download. Note that transfer may take up to 24 hours.
<?php
	}
	if ($vb_requires_shipping && (($vs_shipping_method = $t_order->get('shipping_method')) != 'NONE')) {
?>
</p>
<p>
<?php
	if ($vn_ship_date = $t_order->get('shipped_on_date', array('GET_DIRECT_DATE' => true))) {
		// Already shipped
?>
		Your order was shipped via <?php print $vs_shipping_method; ?> on <?php print date('F j, Y', $vn_ship_date); ?>.
<?php	
	} else {
		if ($vn_ship_date = $t_order->get('shipping_date', array('GET_DIRECT_DATE' => true))) {
			// Not yet shipped but expected shipping date known
?>
			Your order will be shipped via <?php print $vs_shipping_method; ?> on <?php print date('F j, Y', $vn_ship_date); ?>.
<?php		
		} else {
			// Shipping data not known yet.
?>
			Your order will be shipped via <?php print $vs_shipping_method; ?>. You will receive an email when the expected ship date is determined.
<?php
		}
	}
?>
</p>
<?php
	}
?>

<p>Log in at <?php print $this->getVar('login_url'); ?> to review your order under <em>My Account</em> and communicate with the R&R Associate.</p>
  
<p>If you have images ready to download, you may download them through your order overview screen under Account: <?php print $this->getVar('login_url'); ?></p>