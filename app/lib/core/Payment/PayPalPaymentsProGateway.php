<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Payment/PayPalPaymentsProGateway.php :
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
 * @subpackage Payment
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
  require_once(__CA_LIB_DIR__.'/core/Payment/BasePaymentGateway.php');
 
	class PayPalPaymentsProGateway extends BasePaymentGateway {
		# ------------------------------------------------------------------
		protected $ops_gateway_name = 'PayPalPaymentsPro';
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
		 * @param float $pn_transaction_amount Amount of transaction in currency configured in client_services.conf
		 * @param array $pa_payment_info
		 * @param array $pa_customer_info
		 * @param array $pa_options
		 *
		 * @return array An array of response details, or null if an error preventing posting of the request to the gateway occurred
		 */
		public function DoPayment($pn_transaction_amount, $pa_payment_info, $pa_customer_info, $pa_options=null) {
			$ps_currency = (isset($pa_options['currency']) && $pa_options['currency']) ? $pa_options['currency'] : 'USD';
			
			$paymentType = urlencode('Sale');				// or 'Sale'
			$firstName = urlencode($pa_customer_info['billing_fname']);
			$lastName = urlencode($pa_customer_info['billing_lname']);
			$creditCardType = urlencode($pa_payment_info['credit_card_type']);
			
			$va_card_types = array(
				'AMEX' => 'Amex',
				'MC' => 'MasterCard',
				'VISA' => 'Visa',
				'DISC' => 'Discover'
			);
			
			if (isset($va_card_types[$creditCardType])) { 
				$creditCardType = $va_card_types[$creditCardType];
			}
			
			$creditCardNumber = urlencode($pa_payment_info['credit_card_number']);
			$expDateMonth = $pa_payment_info['credit_card_exp_mon'];
			// Month must be padded with leading zero
			$padDateMonth = urlencode(str_pad($expDateMonth, 2, '0', STR_PAD_LEFT));
		
			$expDateYear = urlencode($pa_payment_info['credit_card_exp_yr']);
			$cvv2Number = urlencode($pa_payment_info['credit_card_ccv']);
			$address1 = urlencode($pa_customer_info['billing_address1']);
			$address2 = urlencode($pa_customer_info['billing_address2']);
			$city = urlencode($pa_customer_info['billing_city']);
			$state = urlencode($pa_customer_info['billing_zone']);
			$zip = urlencode($pa_customer_info['billing_postalcode']);
			$country = urlencode($pa_customer_info['billing_country']);				// US or other valid country code
			$email = urlencode($pa_customer_info['billing_email']);
			$phone = urlencode($pa_customer_info['billing_phone']);
			$amount = urlencode($pn_transaction_amount);
			$currencyID = urlencode($ps_currency);							// or other currency ('GBP', 'EUR', 'JPY', 'CAD', 'AUD')
			$note = urlencode(isset($pa_options['note']) ? $pa_options['note'] : '');
			$invoiceID =  date('mdY', $pa_payment_info['created_on']).'-'.$pa_payment_info['order_id'];
			
			
			
			// Add request-specific fields to the request string.
			$nvpStr =	"&PAYMENTACTION=$paymentType&AMT=$amount&CREDITCARDTYPE=$creditCardType&ACCT=$creditCardNumber&IPADDRESS=".$_SERVER['REMOTE_ADDR'].
						"&EXPDATE=$padDateMonth$expDateYear&CVV2=$cvv2Number&FIRSTNAME=$firstName&LASTNAME=$lastName".
						"&STREET=$address1&CITY=$city&STATE=$state&ZIP=$zip&COUNTRYCODE=$country&CURRENCYCODE=$currencyID&INVNUM=$invoiceID&DESC=$note&EMAIL=$email&PHONENUM=$phone";
		
			// Execute the API operation; see the PPHttpPost function above.
			$httpParsedResponseAr = PayPalPaymentsProGateway::PPHttpPost('DoDirectPayment', $nvpStr);
			
			if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
				return array(
					'success' => true,
					'processor' => 'PayPal Payments Pro Gateway',
					'data' => $httpParsedResponseAr,
					'error' => null,
					'transactionID' => $httpParsedResponseAr['TRANSACTIONID']
				);
			} else  {
				return array(
					'success' => false,
					'processor' => 'PayPal Payments Pro Gateway',
					'data' => $httpParsedResponseAr,
					'error' => isset($httpParsedResponseAr['ERROR']) ? urldecode($httpParsedResponseAr['ERROR']) : 'Unknown error',
					'transactionID' => null
				);
			}
		}
		# ------------------------------------------------------------------
		/**
		 * Send HTTP POST Request
		 *
		 * @param	string	The API method name
		 * @param	string	The POST Message fields in &name=value pair format
		 * @return	array	Parsed HTTP Response body
		 */
		static function PPHttpPost($methodName_, $nvpStr_) {
		
			// Set up your API credentials, PayPal end point, and API version.
			$API_UserName = urlencode(__CA_PAYPAL_API_USERNAME__);
			$API_Password = urlencode(__CA_PAYPAL_API_PASSWORD__);
			$API_Signature = urlencode(__CA_PAYPAL_API_SIGNATURE__);
			$API_Endpoint = "https://api-3t.paypal.com/nvp";
			if("sandbox" === __CA_PAYPAL_API_ENVIRONMENT__ || "beta-sandbox" === __CA_PAYPAL_API_ENVIRONMENT__) {
				$API_Endpoint = "https://api-3t.".__CA_PAYPAL_API_ENVIRONMENT__.".paypal.com/nvp";
			}
			$version = urlencode('51.0');
		
			// Set the curl parameters.
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
		
			// Turn off the server and peer verification (TrustManager Concept).
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
		
			// Set the API operation, version, and API signature in the request.
			$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
		
			// Set the request as a POST FIELD for curl.
			curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
		
			// Get response from the server.
			$httpResponse = curl_exec($ch);
		
			if(!$httpResponse) {
				return array(
					"ACK" => "Failure",
					"ERROR" => "$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')'
				);
			}
		
			// Extract the response details.
			$httpResponseAr = explode("&", $httpResponse);
		
			$httpParsedResponseAr = array();
			foreach ($httpResponseAr as $i => $value) {
				$tmpAr = explode("=", $value);
				if(sizeof($tmpAr) > 1) {
					$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
				}
			}
			if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
				return array(
					"ACK" => "Failure",
					"ERROR" => "Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint."
				);
			}
			if (isset($httpParsedResponseAr['L_SEVERITYCODE0']) && ($httpParsedResponseAr['L_SEVERITYCODE0'] == 'Error')) {
				$httpParsedResponseAr['ERROR'] = $httpParsedResponseAr['L_LONGMESSAGE0'];
			}
			return $httpParsedResponseAr;
		}
		# ------------------------------------------------------------------
	}
?>