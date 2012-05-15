<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Payment/BasePaymentGateway.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
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
 * @package CollectiveAccess
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
	class BasePaymentGateway extends BaseObject implements IErrorSetter {
		# ------------------------------------------------------------------
		
		# ------------------------------------------------------------------
		/** 
		 *
		 */
		public function __construct() {
			parent::__construct();
		}
		# ------------------------------------------------------------------
		/** 
		 * Posts sale transaction to credit card gateway
		 *
		 * @param string $ps_payment_type Payment type to invoke gateway with
		 * @param float $pn_transaction_amount Amount of transaction in currency configured in client_services.conf
		 * @param array $pa_payment_info
		 * @param array $pa_customer_info
		 *
		 * @return array An array of response details, or null if an error preventing posting of the request to the gateway occurred
		 */
		public function DoPayment($ps_payment_type, $pn_transaction_amount, $pa_payment_info, $pa_customer_info) {
			die("Must override DoPayment()");
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns name of the payment gateway
		 *
		 * @return string The name of the payment gateway
		 */
		public function getGatewayName() {
			return $this->ops_gateway_name;
		}
		# ------------------------------------------------------------------
	}
?>