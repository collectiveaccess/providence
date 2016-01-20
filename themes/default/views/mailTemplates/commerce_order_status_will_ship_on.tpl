<?php
	$t_order = $this->getVar('t_order');
	$o_client_services_config = caGetClientServicesConfiguration();
	
	$vs_shipping_method = $t_order->get('shipping_method');
	$vn_ship_date = $t_order->get('shipping_date', array('GET_DIRECT_DATE' => true));
	
?>
You were sent the following message by <em><?php print $this->getVar('sender_name'); ?></em> on <em><?php print date('F j, Y g:i a', $this->getVar('sent_on')); ?></em>:

<p>Your order <?php print $t_order->getOrderNumber(); ?> submitted on <?php print date('F j, Y g:i a', (int)$t_order->get('created_on', array('GET_DIRECT_DATE' => true))); ?> will be shipped via <?php print $vs_shipping_method; ?> on <?php print date('F j, Y', $vn_ship_date); ?>.</p>

<p>Log in at <?php print $this->getVar('login_url'); ?> to review your order under <em>My Account</em> and communicate with the R&R Associate.</p>