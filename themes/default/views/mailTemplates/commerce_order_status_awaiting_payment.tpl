<?php
	$t_order = $this->getVar('t_order');
	$o_client_services_config = caGetClientServicesConfiguration();
?>
You were sent the following message by <em><?php print $this->getVar('sender_name'); ?></em> on <em><?php print date('F j, Y g:i a', $this->getVar('sent_on')); ?></em>:

<p>Your order submitted on <?php print date('F j, Y g:i a', (int)$t_order->get('created_on', array('GET_DIRECT_DATE' => true))); ?> is processed and awaiting a payment of <?php print $o_client_services_config->get('currency_symbol').$t_order->getTotal(); ?>.  Log in at <?php print $this->getVar('login_url'); ?> to review your order under <em>My Account</em> and submit payment.</p>

<p>Your order <?php print $t_order->getOrderNumber(); ?> will be fulfilled once payment is received.  Payment is accepted by check or credit card.  All checks should be made payable to "The Historical Society of Pennsylvania" and mailed to the following address: </p>

<p>The Historical Society of Pennsylvania, Attn: Rights and Reproductions, 1300 Locust Street, Philadelphia, PA 19107 </p>

<p>You will be notified when your images are ready for download or your items have been shipped (when applicable).  The average turn-around time from receipt of payment is approximately 2 weeks.<p>   


