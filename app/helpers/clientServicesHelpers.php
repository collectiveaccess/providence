<?php
/** ---------------------------------------------------------------------
 * app/helpers/clientServicesHelpers.php : miscellaneous functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
	$g_caClientServicesNameCache = array(); 


	# ---------------------------------------
	/**
	 * Returns client services configuration
	 *
	 * @return Configuration Returns reference to client services configuration
	 */
	function caGetClientServicesConfiguration() {
		$o_config = Configuration::load();
 		return Configuration::load($o_config->get('client_services_config'));
	}
	# ---------------------------------------
	/**
	 * Formats communication for display in messages list
	 *
	 * @param RequestHTTP $po_request
	 * @param array $pa_data
	 * @param array $pa_options
	 *		viewContentDivID = 
	 *		additionalMessages =
	 *		isAdditionalMessage =
	 *
	 * @return string 
	 */
	function caClientServicesFormatMessageSummary($po_request, $pa_data, $pa_options=null) {
		$vb_is_additional_message = (bool)(isset($pa_options['isAdditionalMessage']) && $pa_options['isAdditionalMessage']);
		$vb_is_unread = !(bool)$pa_data['read_on'];
	
		$vs_unread_class = ($vb_is_unread) ? "caClientCommunicationsMessageSummaryUnread" : "";
		if ($pa_data['source'] == __CA_COMMERCE_COMMUNICATION_SOURCE_INSTITUTION__) { $vb_is_unread = false; $vs_unread_class = ''; }	// institution-sent messages are never unread in Providence
		
		if ($vb_is_additional_message) {
			$vs_class = ($vb_is_unread) ? "caClientCommunicationsAdditionalMessageSummary caClientCommunicationsMessageSummaryUnread" : "caClientCommunicationsAdditionalMessageSummary";
			$vs_buf = "<div class='{$vs_class}' id='caClientCommunicationsMessage_".$pa_data['communication_id']."'>";
		} else {
			$vs_class = ($vb_is_unread) ? "caClientCommunicationsMessageSummary caClientCommunicationsMessageSummaryUnread" : "caClientCommunicationsMessageSummary";
			$vs_buf = "<div class='{$vs_class}'>";
		}
		
		
		$vs_buf .= "<div class='caClientCommunicationsMessageSummaryContainer' id='caClientCommunicationsMessage_".$pa_data['communication_id']."'>";
		$vs_buf .= "<div class='caClientCommunicationsViewMessageIcon'>+</div>";
		TooltipManager::add(
			".caClientCommunicationsViewMessageIcon", _t("View entire message and associated media")
		);
		$vs_buf .= "<div class='caClientCommunicationsMessageSummaryFrom {$vs_unread_class}'><span class='caClientCommunicationsMessageSummaryHeading'>"._t("From").":</span> ".caClientServicesGetSenderName($pa_data);
		$vs_buf .= ($vb_is_unread) ? " <img src='".$po_request->getThemeUrlPath()."/graphics/icons/envelope.gif' border='0'>" : "";
		$vs_buf .= "</div>";
		$vs_buf .= "<div class='caClientCommunicationsMessageSummaryDate {$vs_unread_class}'><span class='caClientCommunicationsMessageSummaryHeading'>"._t("Date").":</span> ".caGetLocalizedDate($pa_data['created_on'], array('dateFormat' => 'delimited'))."</div>";
		$vs_buf .= "<div class='caClientCommunicationsMessageSummarySubject {$vs_unread_class}'><span class='caClientCommunicationsMessageSummaryHeading'>"._t("Subject").":</span> ".$pa_data['subject']."</div>";

		$vs_buf .= "<div class='caClientCommunicationsMessageSummaryText'>".((mb_strlen($pa_data['message']) > 100) ? mb_substr($pa_data['message'], 0, 100)."..." : $pa_data['message'])."</div>";
		$vn_num_additional_messages = is_array($pa_options['additionalMessages']) ? sizeof($pa_options['additionalMessages']) : 0;
		
		// are there orders linked to this thread?
		if ($vn_num_orders = sizeof($va_order_ids = ca_commerce_orders::getOrderIDsForTransaction($pa_data['transaction_id']))) {
			$vs_buf .= "<div class='caClientCommunicationsMessageSummaryThreadButton'>".caNavLink($po_request, (($vn_num_orders == 1) ? _t('%1 order', $vn_num_orders) : _t('%1 orders', $vn_num_orders))." &rsaquo;", 'button', 'client', 'Orders', 'Index', array('transaction_id' => $pa_data['transaction_id']))."</div>\n";
		}
		
		if ($vn_num_additional_messages) {
			$vs_buf .= "<div class='caClientCommunicationsMessageSummaryThreadButton' id='caClientCommunicationsMessageAdditionalCount".$pa_data['communication_id']."'><a href='#' onclick='jQuery(\"#caClientCommunicationsMessageAdditional".$pa_data['communication_id']."\").slideToggle(250, function(){ if(jQuery(\"#caClientCommunicationsMessageViewThread".$pa_data['communication_id']."\").html() == \""._t("view thread")." &rsaquo;\") {jQuery(\"#caClientCommunicationsMessageViewThread".$pa_data['communication_id']."\").html(\""._t("hide thread")." &rsaquo;\")}else{jQuery(\"#caClientCommunicationsMessageViewThread".$pa_data['communication_id']."\").html(\""._t("view thread")." &rsaquo;\")}}); return false;' id='caClientCommunicationsMessageViewThread".$pa_data['communication_id']."' class='button'>"._t("view thread")." &rsaquo;</a></div>\n";
		}		
		$vs_buf .= "</div>";
		
		if ($vn_num_additional_messages) {
			$vs_buf .= "<div class='caClientCommunicationsMessageAdditional' id='caClientCommunicationsMessageAdditional".$pa_data['communication_id']."'>";
			$pa_additional_options = $pa_options;
			unset($pa_additional_options['additionalMessages']);
			$pa_additional_options['isAdditionalMessage'] = true;
			foreach($pa_options['additionalMessages'] as $va_additional_message) {
				$vs_buf .= caClientServicesFormatMessageSummary($po_request, $va_additional_message, $pa_additional_options);
			}
			$vs_buf .= "</div>";
		}
		
		$vs_buf .= "</div>\n";
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Formats communication for display in messages list in Pawtucket
	 *
	 * @param RequestHTTP $po_request
	 * @param array $pa_data
	 * @param array $pa_options
	 *		viewContentDivID = 
	 *		additionalMessages =
	 *		isAdditionalMessage =
	 *
	 * @return string 
	 */
	function caClientServicesFormatMessageSummaryPawtucket($po_request, $pa_data, $pa_options=null) {
		$vb_is_additional_message = (bool)(isset($pa_options['isAdditionalMessage']) && $pa_options['isAdditionalMessage']);
		$vb_is_unread = !(bool)$pa_data['read_on'];
	
		$vs_unread_class = ($vb_is_unread) ? "caClientCommunicationsMessageSummaryUnread" : "";
		if ($po_request->getUserID() == $pa_data['from_user_id']) { $vb_is_unread = false; $vs_unread_class = ''; }	// if the message was created by the user it's already show as "read"
			
		if ($vb_is_additional_message) {
			$vs_class = ($vb_is_unread) ? "caClientCommunicationsAdditionalMessageSummary caClientCommunicationsMessageSummaryUnread" : "caClientCommunicationsAdditionalMessageSummary";
			$vs_buf = "<div class='{$vs_class}' id='caClientCommunicationsMessage_".$pa_data['communication_id']."'>";
		} else {
			$vs_class = ($vb_is_unread) ? "caClientCommunicationsMessageSummary caClientCommunicationsMessageSummaryUnread" : "caClientCommunicationsMessageSummary";
			$vs_buf = "<div class='{$vs_class}'>";
		}
		$vs_buf .= "<div class='caClientCommunicationsMessageSummaryContainer' id='caClientCommunicationsMessage_".$pa_data['communication_id']."'>";
		$vs_buf .= "<div class='caClientCommunicationsViewMessageIcon'>+</div>";
		TooltipManager::add(
			".caClientCommunicationsViewMessageIcon", _t("View entire message and associated media")
		);
		$vs_buf .= "<div class='caClientCommunicationsMessageSummaryFrom {$vs_unread_class}'><span class='caClientCommunicationsMessageSummaryHeading'>"._t("From").":</span> ".caClientServicesGetSenderName($pa_data)."</div>";
		
		$vs_buf .= "<div class='caClientCommunicationsMessageSummaryDate {$vs_unread_class}'><span class='caClientCommunicationsMessageSummaryHeading'>"._t("Date").":</span> ".caGetLocalizedDate($pa_data['created_on'], array('dateFormat' => 'delimited'))."</div>";
		$vs_buf .= "<div class='caClientCommunicationsMessageSummarySubject {$vs_unread_class}'><span class='caClientCommunicationsMessageSummaryHeading'>"._t("Subject").":</span> ".$pa_data['subject']."</div>";
		
		$vs_buf .= "<div class='caClientCommunicationsMessageSummaryText'>".((mb_strlen($pa_data['message']) > 300) ? mb_substr($pa_data['message'], 0, 300)."..." : $pa_data['message'])."</div>";
		$vs_buf .= "</div>";
		$vs_buf .= "</div>\n";
		$vn_num_additional_messages = is_array($pa_options['additionalMessages']) ? sizeof($pa_options['additionalMessages']) : 0;
		
		if ($vn_num_additional_messages) {
			$vs_buf .= "<div class='caClientCommunicationsMessageSummaryViewButton' id='caClientCommunicationsMessageAdditionalCount".$pa_data['communication_id']."'><a href='#' onclick='jQuery(\"#caClientCommunicationsMessageAdditional".$pa_data['communication_id']."\").slideToggle(250); jQuery(\".caClientCommunicationsMessageSummaryViewButton\").hide(); return false;' >"._t("View thread")." &rsaquo;</a></div>";
		}		
		if ($vn_num_additional_messages) {
			$vs_buf .= "<div class='caClientCommunicationsMessageAdditional' id='caClientCommunicationsMessageAdditional".$pa_data['communication_id']."'>";
			$pa_additional_options = $pa_options;
			unset($pa_additional_options['additionalMessages']);
			$pa_additional_options['isAdditionalMessage'] = true;
			foreach($pa_options['additionalMessages'] as $va_additional_message) {
				$vs_buf .= caClientServicesFormatMessageSummaryPawtucket($po_request, $va_additional_message, $pa_additional_options);
			}
			$vs_buf .= "</div>";
		}
		
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Formats communication for display in message window
	 *
	 * @param RequestHTTP $po_request
	 * @param array $pa_data
	 * @param array $pa_options
	 *		viewContentDivID = 
	 *		replyButton = 
	 *
	 * @return string 
	 */
	function caClientServicesFormatMessage($po_request, $pa_data, $pa_options=null) {
		$vs_buf = "<div class='caClientCommunicationsMessage'>";
		
		if (isset($pa_options['replyButton']) && $pa_options['replyButton']) {
			$vs_buf .= "<div style='float: right; clear: both;'>".$pa_options['replyButton']."</div>";
		}
		$vs_buf .= "<div class='caClientCommunicationsMessageFrom'><span class='caClientCommunicationsMessageHeading'>"._t('From').":</span> ".caClientServicesGetSenderName($pa_data)."</div>";
		$vs_buf .= "<div class='caClientCommunicationsMessageDate'><span class='caClientCommunicationsMessageHeading'>"._t("Date").":</span> ".caGetLocalizedDate($pa_data['created_on'], array('dateFormat' => 'delimited'))."</div>";
		$vs_buf .= "<div class='caClientCommunicationsMessageSubject'><span class='caClientCommunicationsMessageHeading'>"._t('Subject').":</span> ".$pa_data['subject']."</div>";
		
		$vs_buf .= "<div class='caClientCommunicationsMessageText'>".nl2br($pa_data['message'])."</div>";
		$vs_buf .= "</div>\n";
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Formats communication for display in message window in Pawtucket
	 *
	 * @param RequestHTTP $po_request
	 * @param array $pa_data
	 * @param array $pa_options
	 *		viewContentDivID = 
	 *		replyButton = 
	 *
	 * @return string 
	 */
	function caClientServicesFormatMessagePawtucket($po_request, $pa_data, $pa_options=null) {
		$vs_buf = "<div class='caClientCommunicationsMessage'>";
		
		if (isset($pa_options['replyButton']) && $pa_options['replyButton']) {
			$vs_buf .= "<div id='reply'>".$pa_options['replyButton']."</div>";
		}
		$vs_buf .= "<div class='caClientCommunicationsMessageFrom'><span class='caClientCommunicationsMessageHeading'>"._t('From').":</span> ".caClientServicesGetSenderName($pa_data)."</div>";
		$vs_buf .= "<div class='caClientCommunicationsMessageDate'><span class='caClientCommunicationsMessageHeading'>"._t("Date").": </span>".caGetLocalizedDate($pa_data['created_on'], array('dateFormat' => 'delimited'))."</div>";
		$vs_buf .= "<div class='caClientCommunicationsMessageSubject'><span class='caClientCommunicationsMessageHeading'>"._t('Subject').":</span> ".$pa_data['subject']."</div>";
		
		$vs_buf .= "<div class='caClientCommunicationsMessageText'>".nl2br($pa_data['message'])."</div>";
		$vs_buf .= "</div>\n";
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * 
	 *
	 * @param array $pa_data
	 * @param array $pa_options
	 *
	 * @return string 
	 */
	function caClientServicesGetSenderName($pa_data, $pa_options=null) {
		global $g_caClientServicesNameCache;
		if (!isset($g_caClientServicesNameCache[$pa_data['from_user_id']])) {
			$t_user = new ca_users($pa_data['from_user_id']);
			return $g_caClientServicesNameCache[$pa_data['from_user_id']] = $t_user->get('fname').' '.$t_user->get('lname');
		} else {
			return $g_caClientServicesNameCache[$pa_data['from_user_id']];
		}
	}
	# ---------------------------------------
	/**
	 * Perform basic validation on credit card number
	 *
	 * @param string $ps_credit_card_number Credit card number
	 * @return string Card type code if valid, false if not
	 */
	function caValidateCreditCardNumber($ps_credit_card_number) {
		$ps_credit_card_number = preg_replace('![^0-9]+!', '', $ps_credit_card_number);
		$vs_card_type = "";
		$va_card_regexes = array(
			"/^4\d{12}(\d\d\d){0,1}$/" => "VISA",
			"/^5[12345]\d{14}$/"       => "MASTERCARD",
			"/^3[47]\d{13}$/"          => "AMEX",
			"/^6011\d{12}$/"           => "DISCOVER",
			"/^30[012345]\d{11}$/"     => "DINERS",
			"/^3[68]\d{12}$/"          => "DINERS",
		);
		
		foreach ($va_card_regexes as $vs_regex => $vs_type) {
			if (preg_match($vs_regex, $ps_credit_card_number)) {
				$vs_card_type = $vs_type;
				break;
			}
		}
		
		if (!$vs_card_type) {
			return $false;
		}
		
		/*  mod 10 checksum algorithm  */
		$vn_revcode = strrev($ps_credit_card_number);
		$vn_checksum = 0; 
		
		for ($vn_i = 0; $vn_i < strlen($vn_revcode); $vn_i++) {
			$vn_current_num = intval($vn_revcode[$vn_i]);  
			if($vn_i & 1) {  // odd
				$vn_current_num *= 2;
			}
	
			$vn_checksum += $vn_current_num % 10; 
			if ($vn_current_num >  9) {
				$vn_checksum += 1;
			}
		}
		
		if ($vn_checksum % 10 == 0) {
			return $vs_card_type;
		}
		return false;
	}
	# ---------------------------------------
?>