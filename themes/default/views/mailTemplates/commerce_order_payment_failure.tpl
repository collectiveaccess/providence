<?php
	$t_order = $this->getVar('t_order');
	$o_client_services_config = caGetClientServicesConfiguration();
	$va_response = $this->getVar('response');
	$vs_gateway_name = $this->getVar('gateway');
?>
<p>Processing of payment for order <?php print $t_order->getOrderNumber(); ?> submitted on <?php print date('F j, Y g:i a', (int)$t_order->get('created_on', array('GET_DIRECT_DATE' => true))); ?> by <?php print $vs_gateway_name; ?> has failed.</p>
<p>Details:

<?php
	print_R($va_response);	
?>
</p>

