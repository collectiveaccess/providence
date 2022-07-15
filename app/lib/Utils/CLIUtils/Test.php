<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Test.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 
require_once(__CA_APP_DIR__."/helpers/mailHelpers.php");
trait CLIUtilsTest { 
	# -------------------------------------------------------
	/**
	 * Rebuild search indices
	 */
	public static function test_outgoing_email_configuration($opts=null) {
		global $g_last_email_error;
		
		if(!defined('__CA_SMTP_SERVER__') || !__CA_SMTP_SERVER__) { 
			CLIUtils::addError(_t('SMTP server is not configured'));
			return false;
		}
		if(!defined('__CA_SMTP_PORT__') || !__CA_SMTP_PORT__) { 
			CLIUtils::addError(_t('SMTP port is not configured'));
			return false;
		}
		
		$to = $opts->getOption('to');
		if(!$to) { 
			CLIUtils::addError(_t('A destination email address must be specified'));
			return false;
		}
		if(!caCheckEmailAddress($to)) { 
			CLIUtils::addError(_t('Email %1 address is invalid', $to));
			return false;
		}
		if(!caSendmail($to, __CA_ADMIN_EMAIL__, _t("[%1] Test email message", __CA_APP_DISPLAY_NAME__), 
			_t("This is a test of the %1 system outgoing email configuration. If you are receiving this message then your outgoing email configuration appears to work!", __CA_APP_DISPLAY_NAME__),
			_t("This is a test of the <em>%1</em> system outgoing email configuration. If you are receiving this message then your outgoing email configuration appears to work!", __CA_APP_DISPLAY_NAME__)
		)) {
			CLIUtils::addError(_t("Failed to send test email to %1: %2", $to, $g_last_email_error));
			return false;
		} else {
			CLIUtils::addMessage(_t("Sent test email to %1", $to));
		}

		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function test_outgoing_email_configurationParamList() {
		return [
			"to|t-s" => _t('Email address to send test message to.')
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function test_outgoing_email_configurationUtilityClass() {
		return _t('Test');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function test_outgoing_email_configurationHelp() {
		return _t("In order to send email notifications to users an outgoing email server must be configured in the setup.php file (see https://manual.collectiveaccess.org/setup/setup.php.html#outgoing-email for details on configuring a server). Use this utility to test your configuration by sending a test email to the specified address.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function test_outgoing_email_configurationShortHelp() {
		return _t("Test outgoing email configuration.");
	}
	# -------------------------------------------------------
}
